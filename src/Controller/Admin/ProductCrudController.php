<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Category;
use App\Enum\ShippingOption;
use App\Enum\ColorsOption;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;


class ProductCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    
 public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            TextField::new('lp', 'Lp'),
            TextField::new('kod', 'Kod'),

            ImageField::new('imageFilename', 'Obraz')
                ->setBasePath('uploads/products')
                ->setUploadDir('public/uploads/products')
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false),

            TextField::new('nazwaProduktu', 'Nazwa produktu'),

            TextField::new('amount', 'Ilość')->hideOnIndex(),

            TextField::new('cena_netto', 'Cena netto'),
            TextField::new('vat', 'VAT'),
            TextField::new('cena_brutto', 'Cena brutto'),

            TextField::new('value', 'Wartość')->hideOnIndex(),

            AssociationField::new('category', 'Kategoria'),

            ChoiceField::new('shippingOption', 'Opcja dostawy')
                ->setChoices([
                    'Transport krajowy' => ShippingOption::DOMESTIC,
                    'Transport zagraniczny' => ShippingOption::INTERNATIONAL,
                    'Ekspresowa dostawa' => ShippingOption::EXPRESS,
                    'Nieznana' => ShippingOption::UNKNOWN,
                ])
                ->renderExpanded(false)
                ->renderAsBadges(),

            ChoiceField::new('colorsOption', 'Kolor')
                ->setChoices([
                    // 'Czerwony' => ColorsOption::RED,
                    'Biały' => ColorsOption::WHITE,
                    'Czarny' => ColorsOption::BLACK,
                    'Żółty' => ColorsOption::YELLOW,
                ])
                ->renderExpanded(false)
                ->renderAsBadges(),
        ];
    }
    // public function configureCrud(Crud $crud): Crud
    // {
    //     return $crud
    //         ->setPageTitle('index', 'Produkty')
    //         ->setPageTitle('new', 'Dodaj produkt')
    //         ->setPageTitle('edit', 'Edytuj produkt');
    // }
}


