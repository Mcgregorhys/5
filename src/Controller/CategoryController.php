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

final class CategoryController extends AbstractController
{
   #[Route('/category', name: 'app_category')]
public function index(CategoryRepository $categoryRepository): Response
{
    $categories = $categoryRepository->findAll();

    return $this->render('category/index.html.twig', [
        'categories' => $categories,
    ]);
}
#[Route('/category/new', name: 'app_category_new')]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $category = new Category();
    $form = $this->createForm(CategoryTypeForm::class, $category);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($category);
        $em->flush();

        $this->addFlash('success', 'Kategoria została dodana.');

        // return $this->redirectToRoute('app_category');
        return $this->redirectToRoute('product_list');
    }

    return $this->render('category/new.html.twig', [
        'form' => $form->createView(),
    ]);
}


#[Route('/products', name: 'product_list')]
public function list(CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
{
    $products = $productRepository->findAll();
    $categories = $categoryRepository->findAll();

    return $this->render('product/list.html.twig', [
        'products' => $products,
        'categories' => $categories,
    ]);
}
}
