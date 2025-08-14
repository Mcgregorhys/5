<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
//dodaję
use App\Entity\Product;
//use App\Form\ProductTypeForm;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\ShippingOption;
use App\Enum\ColorsOption;

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
        'colors_option' => ColorsOption::cases(),

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
             if (isset($data['shippingOption'])) {
                $shippingOption = ShippingOption::safeFrom($data['shippingOption']);
                $product->setShippingOption($shippingOption?->value);
            }
          // Obsługa colorsOption
             if (isset($data['colorsOption'])) {
                $colorsOptionValue = $data['colorsOption'];
                $colorsOption = \App\Enum\ColorsOption::tryFrom($colorsOptionValue);

                if ($colorsOption !== null || empty($colorsOptionValue)) {
                    $product->setColorsOption($colorsOption);
                } else {
                    $this->addFlash('warning', "Nieprawidłowa wartość koloru dla produktu ID: {$id}");
                    continue; // Pamiętaj o tej poprawce
                }
            }
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

    return $this->redirectToRoute('product_param');
}


}    

   