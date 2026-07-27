<?php

namespace App\Form;

use App\Enum\ApiPermission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApiKeyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $permissionChoices = [];
        foreach (ApiPermission::all() as $permission) {
            $permissionChoices[$permission->label()] = $permission->value;
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa klucza',
                'help' => 'Opisowa nazwa, np. "Integracja sklepu" lub "Eksport danych"',
                'constraints' => [new NotBlank(message: 'Podaj nazwę klucza.')],
            ])
            ->add('permissions', ChoiceType::class, [
                'label' => 'Uprawnienia API',
                'choices' => $permissionChoices,
                'multiple' => true,
                'expanded' => true,
                'help' => 'Zaznacz, do których zasobów klucz ma mieć dostęp.',
                'constraints' => [
                    new Count(min: 1, minMessage: 'Wybierz co najmniej jedno uprawnienie.'),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Utwórz klucz API',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
