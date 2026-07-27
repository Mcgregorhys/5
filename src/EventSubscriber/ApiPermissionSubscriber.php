<?php

namespace App\EventSubscriber;

use App\Attribute\RequireApiPermission;
use App\Entity\User;
use App\Security\ApiKeyUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class ApiPermissionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $requiredPermissions = $this->resolveRequiredPermissions($event);
        if ([] === $requiredPermissions) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof ApiKeyUser) {
            foreach ($requiredPermissions as $permission) {
                if (!$user->hasPermission($permission)) {
                    $event->setController(fn () => new JsonResponse(
                        ['error' => sprintf('Brak uprawnienia: %s', $permission)],
                        Response::HTTP_FORBIDDEN
                    ));

                    return;
                }
            }

            return;
        }

        // Zalogowany użytkownik aplikacji webowej — pełny dostęp do API w przeglądarce
        if ($user instanceof User) {
            return;
        }

        if ($this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $event->setController(fn () => new JsonResponse(
            ['error' => 'Wymagane logowanie lub klucz API (nagłówek X-API-Key).'],
            Response::HTTP_UNAUTHORIZED
        ));
    }

    /**
     * @return list<string>
     */
    private function resolveRequiredPermissions(ControllerEvent $event): array
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return [];
        }

        [$object, $method] = $controller;
        $reflectionClass = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($method);

        $permissions = [];
        foreach ([$reflectionClass, $reflectionMethod] as $reflection) {
            foreach ($reflection->getAttributes(RequireApiPermission::class) as $attribute) {
                /** @var RequireApiPermission $instance */
                $instance = $attribute->newInstance();
                $permissions[] = $instance->permission;
            }
        }

        return array_values(array_unique($permissions));
    }
}
