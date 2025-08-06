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

class ParamController extends AbstractController
{
    #[Route('/param', name: 'product_param')]
    public function listParam(Request $request, EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();

        // Sprawdzenie czy formularz został wysłany
        if ($request->isMethod('POST')) {
            $productsData = $request->request->all('products');
            $action = $request->request->get('action');

            if ($action === 'update_param') {
                foreach ($productsData as $id => $data) {
                    $product = $entityManager->getRepository(Product::class)->find($id);
                    if ($product) {
                        // Obsługa shippingOption
                        if (isset($data['shippingOption'])) {
                            $shippingOption = ShippingOption::safeFrom($data['shippingOption']);
                            $product->setShippingOption($shippingOption);
                        }
                    }
                }
                $entityManager->flush();
                $this->addFlash('success', 'Produkty zostały zaktualizowane.');
                return $this->redirectToRoute('product_param');
            }
    }
    return $this->render('product/param.html.twig', [
        'products' => $products,
        'shipping_options' => ShippingOption::cases(),
        'edit_mode' => true,
    ]);
}


#[Route('/product/edit-param', name: 'product_edit_param', methods: ['POST'])]
public function editOrDeleteParam(Request $request, EntityManagerInterface $entityManager): Response
{
    $productsData = $request->request->all('products');
    $toDelete = $request->request->all('to_delete');
    $removeImage = $request->request->all('remove_image');
    $images = $request->files->get('product_images', []);
    $action = $request->request->get('action');

    
    if ($action === 'update_param') {
    foreach ($productsData as $id => $data) {
        $product = $entityManager->getRepository(Product::class)->find($id);
        
        if ($product) {
            
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
               
                $product->setNazwaProduktu($data['nazwaProduktu']);
               

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

    return $this->redirectToRoute('product_param');
}


}    

   