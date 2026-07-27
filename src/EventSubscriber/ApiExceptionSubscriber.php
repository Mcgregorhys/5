<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 10],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Nie dotyczy /api-keys (zarządzanie kluczami w panelu webowym)
        if (str_starts_with($request->getPathInfo(), '/api-keys')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof AuthenticationException) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Wymagane logowanie lub klucz API (nagłówek X-API-Key).'],
                Response::HTTP_UNAUTHORIZED
            ));

            return;
        }

        if ($throwable instanceof AccessDeniedException || $throwable instanceof AccessDeniedHttpException) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Brak uprawnień do tego zasobu API. Zaloguj się w aplikacji lub użyj klucza API z odpowiednimi uprawnieniami.'],
                Response::HTTP_FORBIDDEN
            ));
        }
    }
}
