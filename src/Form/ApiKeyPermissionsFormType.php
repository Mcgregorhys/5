<?php

namespace App\Form;

use App\Enum\ApiPermission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class ApiKeyPermissionsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $permissionChoices = [];
        foreach (ApiPermission::all() as $permission) {
            $permissionChoices[$permission->label()] = $permission->value;
        }

        $builder
            ->add('permissions', ChoiceType::class, [
                'label' => 'Uprawnienia API',
                'choices' => $permissionChoices,
                'multiple' => true,
                'expanded' => true,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Wybierz co najmniej jedno uprawnienie.'),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Klucz aktywny',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Zapisz zmiany',
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
