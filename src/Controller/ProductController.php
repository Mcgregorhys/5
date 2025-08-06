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
use App\Enum\ShippingOption;

class ProductController extends AbstractController
{
    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        //tworzę zminną produkt jako nowy obiekt klasy produkt
        $product = new Product();

        $product->setShippingOption(ShippingOption::DOMESTIC); // Domyślnie "Transport krajowy"
        
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
            $product->setNettoMinus30(netto_minus30: $cenaNetto - ($cenaNetto * 0.3));
            $product->setLp(0);
             $product->setCenaBrutto($cenaNetto + ($cenaNetto * $vat / 100));
            $product->setNettoMinus20($cenaNetto - ($cenaNetto * 0.2));
            $product->setNettoMinus30($cenaNetto - ($cenaNetto * 0.3));
            // Obliczenie wartości w EUR z rabatem
            $product->setEurMinus20($product->getCenaBrutto() * 0.8);
            $product->setEurMinus30($product->getCenaBrutto() * 0.7);
            // zapis do bazy danych (jeśli używane jest Doctrine)
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

    #[Route('/discounts', name: 'product_discounts')]
    public function listDiscounts(EntityManagerInterface $entityManager): Response
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
        // Obliczenie wartości z rabatem
        foreach ($products as $product) {
            $cenaNetto = $product->getCenaNetto();
            $vat = $product->getVat();
            $product->setCenaBrutto($cenaNetto + ($cenaNetto * $vat / 100));
            $product->setNettoMinus20($cenaNetto - ($cenaNetto * 0.2));
            $product->setNettoMinus30($cenaNetto - ($cenaNetto * 0.3));
            // Obliczenie wartości w EUR z rabatem
            $product->setEurMinus20($product->getCenaBrutto() * 0.8);
            $product->setEurMinus30($product->getCenaBrutto() * 0.7);
        }

        return $this ->render('product/list_discounts.html.twig', [
            'products'=> $products,
            'totalValue' => $totalValue,
        ]);
    }

    

    


     
       
    
    //****************************** */
    
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

    // error_log('=== Products Data ===');
    // error_log(print_r($productsData, true));
    // error_log('=== To Delete ===');
    // error_log(print_r($toDelete, true));
    // error_log('=== Remove Image ===');
    // error_log(print_r($removeImage, true));
    // error_log('=== Images ===');
    // error_log(print_r($images, true));
    // error_log('=== Action ===');
    // error_log($action);



    if ($action === 'delete') {
        foreach ($toDelete as $id) {
            $product = $entityManager->getRepository(Product::class)->find($id);
            if ($product) {
                if ($product->getImageFilename()) {
                    $imagePath = $this->getParameter('product_images_directory').'/'.$product->getImageFilename();
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
           // Debugowanie wartości shippingOption
            // error_log("ShippingOption value before processing: " . print_r($data['shippingOption'], true));
            
            // Obsługa shippingOption
            if (isset($data['shippingOption'])) {
                if ($data['shippingOption'] instanceof ShippingOption) {
                    $product->setShippingOption($data['shippingOption']->value);
                } elseif (is_string($data['shippingOption']) || is_int($data['shippingOption'])) {
                    $product->setShippingOption($data['shippingOption']);
                } else {
                    $product->setShippingOption(null);
                }
            }
            // Aktualizacja podstawowych pól
                $product->setKod((int)$data['kod']);
                $product->setNazwaProduktu($data['nazwaProduktu']);
                $product->setCenaNetto((float)$data['cenaNetto']);
                $product->setVat((int)$data['vat']);
                $product->setNettoMinus30($product->getCenaNetto() - ($product->getCenaNetto() * 0.3));
                
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
            
            // Reszta aktualizacji...
        }
    }
    $entityManager->flush();
    $this->addFlash('success', 'Produkty zostały zaktualizowane.');
}

    return $this->redirectToRoute('product_list');
}

}