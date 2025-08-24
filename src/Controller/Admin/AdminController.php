<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {  
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
        // return parent::index();
    }
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Panel Administracyjny');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Użytkownicy', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Produkty', 'fas fa-box', Product::class);
        yield MenuItem::linkToCrud('Kategorie', 'fas fa-list', Category::class); 
        yield MenuItem::linkToUrl('Powrót do strony głównej', 'fas fa-arrow-left', '/');       
    }
}