<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
//dodaję
use App\Entity\Product;
use App\Form\ProductTypeForm;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProductController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
 
    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        //tworzę zminną produkt jako nowy obiekt klasy produkt
        $product = new Product();
        //tworzę zmienną form i przypisuję ją do metody createform i przekazuję jako argumenty klasę ProductType oraz product
        $form = $this->createForm(ProductTypeForm::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

             $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('product_images_directory'), // ustalona ścieżka w services.yaml
                $newFilename
            );
            $product->setImageFilename($newFilename);
        }
            //Wyliczenie ceny brutto
            $cenaNetto = $product ->getCenaNetto();
            $vat = $product -> getVat();
            $product->setCenaBrutto($cenaNetto +($cenaNetto * $vat/ 100));
            $amount = $product->getAmount();
            $product->setAmount( $amount);
            $product->setValue($cenaNetto * $amount);
            $product->setLp(0);
            //zapis do bazy danych (jeśli używane jest Doctrine)
            $entityManager->persist($product);
            $entityManager->flush();

            //przekiewowanie po zapisaniu
            return $this->redirectToRoute('product_list');

        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/', name: 'product_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        //pobranie wszystkich produktów z bazy danych
        $products = $entityManager->getRepository(Product::class)->findAll();
        //Renderowanie widoku i przekazanie produktów

          // Ustawienie liczby porządkowej
        $lp = 1;
        $totalValue = 0;
        foreach ($products as $product) {
            $product->setLp($lp++);
            $totalValue += $product->getValue();
        }

        return $this ->render('product/list.html.twig', [
            'products'=> $products,
            'totalValue' => $totalValue,
        ]);
    }
    
    #[Route('/product/delete-selected', name: 'product_delete_selected', methods: ['POST'])]
    public function deleteSelected(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Pobierz identyfikatory zaznaczonych produktów
        $selectedProducts = $request->request->all('selected_products');
    
        if (!empty($selectedProducts)) {
            // Pobierz produkty z bazy danych
            $products = $entityManager->getRepository(Product::class)->findBy(['id' => $selectedProducts]);
    
            // Usuń produkty
            foreach ($products as $product) {
                $entityManager->remove($product);
            }
    
            $entityManager->flush();
    
            $this->addFlash('success', 'Zaznaczone produkty zostały usunięte.');
        } else {
            $this->addFlash('warning', 'Nie wybrano żadnych produktów do usunięcia.');
        }
    
        return $this->redirectToRoute('product_list');
    }

#[Route('/product/edit-or-delete', name: 'product_edit_or_delete', methods: ['POST'])]
public function editOrDelete(Request $request, EntityManagerInterface $entityManager,LoggerInterface $logger): Response 
{
    $productsData = $request->request->all('products');
    $toDelete = $request->request->all('to_delete');
    $removeImage = $request->request->all('remove_image');
    $images = $request->files->get('products', []);
    $action = $request->request->get('action');

    if ($action === 'delete') {
        foreach ($toDelete as $id) {
            $product = $entityManager->getRepository(Product::class)->find($id);
            if ($product) {
                $this->removeProductImage($product, $logger);
                $entityManager->remove($product);
            }
        }
        $entityManager->flush();
        $this->addFlash('success', 'Wybrane produkty zostały usunięte.');
        return $this->redirectToRoute('product_list');
    }

    if ($action === 'update') {
        foreach ($productsData as $id => $data) {
            $product = $entityManager->getRepository(Product::class)->find($id);

            if ($product) {
                $product->setNazwaProduktu($data['nazwaProduktu']);
                $product->setCenaNetto((float)$data['cenaNetto']);
                $product->setVat((int)$data['vat']);
                $product->setAmount((int)$data['amount']);
                
                $this->calculatePrices($product);

                if (isset($removeImage[$id])) {
                    $this->removeProductImage($product, $logger);
                }

                if (isset($images[$id]['imageFile']) && $images[$id]['imageFile'] instanceof UploadedFile) {
                    $this->handleProductImageUpload($product, $images[$id]['imageFile']);
                }
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'Produkty zostały zaktualizowane.');
    }

    return $this->redirectToRoute('product_list');
}

private function removeProductImage(Product $product): void
{
    if ($product->getImageFilename()) {
        $imagePath = $this->getParameter('product_images_directory') . '/' . $product->getImageFilename();
        try {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $product->setImageFilename(null);
        } catch (\Exception $e) {
            $this->logger->error('Error removing image: ' . $e->getMessage());
        }
    }
}

private function handleProductImageUpload(Product $product, ?UploadedFile $imageFile = null): void
{
    // Sprawdź czy plik został przesłany i jest prawidłowy
    if (!$imageFile || !$imageFile->isValid()) {
        error_log('No valid file was uploaded');
        return;
    }

    // Sprawdź typ MIME
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
        error_log('Invalid file type: ' . $imageFile->getMimeType());
        return;
    }

    // Usuń stare zdjęcie jeśli istnieje
    $this->removeProductImage($product);

    // Generuj unikalną nazwę pliku
    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
    $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

    // Przenieś plik do katalogu docelowego
    try {
        $imageFile->move(
            $this->getParameter('product_images_directory'),
            $newFilename
        );
        $product->setImageFilename($newFilename);
    } catch (FileException $e) {
        error_log('File upload error: ' . $e->getMessage());
    }
}

private function calculatePrices(Product $product): void
{
    $cenaNetto = $product->getCenaNetto();
    $vat = $product->getVat();
    $amount = $product->getAmount();
    
    $cenaBrutto = $cenaNetto * (1 + $vat / 100);
    $value = $cenaBrutto * $amount;
    
    $product->setCenaBrutto($cenaBrutto);
    $product->setValue($value);
}


}