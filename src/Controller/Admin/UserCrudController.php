<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

//class UserCrudController extends BaseCrudController implements EventSubscriberInterface
class UserCrudController extends BaseCrudController
{
    // public function __construct(
    //     private UserPasswordHasherInterface $passwordHasher
    // ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Użytkownicy')
            ->setPageTitle('new', 'Dodaj użytkownika')
            ->setPageTitle('edit', 'Edytuj użytkownika')
            ->setPageTitle('detail', 'Szczegóły użytkownika')
            ->setEntityLabelInSingular('Użytkownik')
            ->setEntityLabelInPlural('Użytkownicy')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),

            // hasło tylko w formularzu, nie w tabeli
            TextField::new('password', 'Hasło')
                ->onlyOnForms()
                ->setRequired(false), // żeby można było edytować usera bez zmiany hasła

            ChoiceField::new('roles', 'Role')
                ->setChoices([
                    'Użytkownik' => 'ROLE_USER',
                    'Sprzedawca' => 'ROLE_SPRZEDAWCA',
                    'Manager' => 'ROLE_MANAGER',
                    'Admin' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(),
        ];
    }

    /**
     * Rejestrujemy eventy EasyAdmin
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'hashPassword',
            BeforeEntityUpdatedEvent::class   => 'hashPassword',
        ];
    }

    /**
     * Hashujemy hasło, jeśli zostało podane w formularzu
     */
    public function hashPassword($event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof User) {
            return;
        }

        $plainPassword = $entity->getPassword();

        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($entity, $plainPassword);
            $entity->setPassword($hashedPassword);
        }
    }
}
