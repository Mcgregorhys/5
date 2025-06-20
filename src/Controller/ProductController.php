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

class ProductController extends AbstractController
{
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
public function editOrDelete(Request $request, EntityManagerInterface $entityManager): Response
{
    $productsData = $request->request->all('products');
    $toDelete = $request->request->all('to_delete');
    $removeImage = $request->request->all('remove_image');
    $images = $request->files->get('product_images', []);
    $action = $request->request->get('action');

    if ($action === 'delete') {
        foreach ($toDelete as $id) {
            $product = $entityManager->getRepository(Product::class)->find($id);
            if ($product) {
                // Usuń zdjęcie z dysku
                if ($product->getImageFilename()) {
                    $imagePath = $this->getParameter('product_images_directory') . '/' . $product->getImageFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
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
                // Aktualizacja podstawowych pól
                $product->setNazwaProduktu($data['nazwaProduktu']);
                $product->setCenaNetto((float)$data['cenaNetto']);
                $product->setVat((int)$data['vat']);
                
                // Aktualizacja amount z walidacją
                $amount = (int)$data['amount'];
                if ($amount >= 0) {
                    $product->setAmount($amount);
                } else {
                    $this->addFlash('warning', "Nieprawidłowa wartość ilości dla produktu ID: {$id}");
                    continue;
                }
                
                // Obliczenie cen i wartości
                $cenaNetto = $product->getCenaNetto();
                $vat = $product->getVat();
                $product->setCenaBrutto($cenaNetto * (1 + $vat / 100));
                $product->setValue($product->getCenaBrutto() * $product->getAmount());

                // Obsługa zdjęć
                if (!$product->getImageFilename() && isset($images[$id])) {
                    $imageFile = $images[$id];
                    if ($imageFile && $imageFile->isValid()) {
                        $newFilename = uniqid().'.'.$imageFile->guessExtension();
                        $imageFile->move(
                            $this->getParameter('product_images_directory'),
                            $newFilename
                        );
                        $product->setImageFilename($newFilename);
                    }
                }

                if (in_array($id, $removeImage)) {
                    $imagePath = $this->getParameter('product_images_directory') . '/' . $product->getImageFilename();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $product->setImageFilename(null);
                }
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'Produkty zostały zaktualizowane.');
    }

    return $this->redirectToRoute('product_list');
}

}