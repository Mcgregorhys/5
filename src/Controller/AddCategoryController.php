<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryTypeForm;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // <-- to musi być
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;

final class AddCategoryController extends AbstractController
{
   #[Route('/add_category', name: 'add-category')]
public function AdCategory(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
{
    $products = $entityManager->getRepository(Product::class)->findAll();
    $categories = $categoryRepository->findAll();

    // Sprawdzenie czy formularz został wysłany
    if ($request->isMethod('POST')) {
        $productsData = $request->request->all('products');
        $action = $request->request->get('action');

        if ($action === 'add-category') {
            foreach ($productsData as $id => $data) {
                $product = $entityManager->getRepository(Product::class)->find($id);

                if ($product) {
                    if (isset($data['category'])) {
                        $categoryId = (int) $data['category'];
                        if ($categoryId > 0) {
                            $category = $categoryRepository->find($categoryId);
                            if ($category) {
                                $product->setCategory($category);
                            } else {
                                $this->addFlash('warning', "Nie znaleziono kategorii ID: {$categoryId} dla produktu ID: {$id}");
                            }
                        } else {
                            $product->setCategory(null);
                        }
                    }

                    $product->setNazwaProduktu($data['nazwaProduktu'] ?? $product->getNazwaProduktu());
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Produkty zostały zaktualizowane.');
            return $this->redirectToRoute('add-category');
        }
    }

    return $this->render('product/add_category.html.twig', [
        'products' => $products,
        'categories' => $categories,
    ]);
}
}

   

