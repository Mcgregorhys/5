<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class ProductTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

    $builder
    ->add('nazwaProduktu', TextType::class, [
        'label' => 'Nazwa produktu',
    ])
    ->add('amount', NumberType::class, [
        'label' => 'Ilość',
        'attr' => ['step' => '1'],
    ])
    ->add('cenaNetto', NumberType::class, [
        'label' => 'Cena netto',
        'attr' => ['step' => '0.01'],
    ])
    ->add('vat', NumberType::class, [
        'label' => 'VAT (%)',
        'attr' => ['step' => '0.01'],
    ])
    ->add('cenaBrutto', NumberType::class, [
        'label' => 'Cena brutto',
        'mapped' => false, // Pole nie jest mapowane na encję
        'attr' => ['readonly' => true], // Pole jest tylko do odczytu
    ])
    ->add('wartoscMagazynowa', NumberType::class, [
        'label' => 'Wartość magazynowa',
        'mapped' => false, // Pole nie jest mapowane na encję
        'attr' => ['readonly' => true], // Pole jest tylko do odczytu
    ])
    //dodanie pola do przesyłania pliku obrazu
    ->add('imageFile', FileType::class, [
        'label' => 'Zdjęcie produktu',
        'mapped' => false, // ważne!
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '2M',
                'mimeTypes' => ['image/jpeg', 'image/png'],
                'mimeTypesMessage' => 'Dodaj poprawny plik JPG lub PNG.',
            ]),
        ],
    ]);
}

 function configureOptions(OptionsResolver $resolver): void
{
$resolver->setDefaults([
    'data_class' => Product::class,
]);
}


}