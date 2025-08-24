<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Templates;

abstract class BaseCrudController extends AbstractCrudController
{
    public function configureTemplates(): Templates
    {
        return Templates::new()
            ->withTemplates([
                'layout' => 'admin/my_layout.html.twig',
            ]);
    }
}