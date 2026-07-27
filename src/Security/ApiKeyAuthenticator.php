<?php

namespace App\Security;

use App\Repository\ApiKeyRepository;
use App\Service\ApiKeyManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ApiKeyManager $apiKeyManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return false;
        }

        return null !== $this->extractToken($request);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);

        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('Brak klucza API. Użyj nagłówka X-API-Key lub Authorization: Bearer.');
        }

        $apiKey = $this->apiKeyRepository->findActiveByTokenHash(
            $this->apiKeyManager->hashToken($token)
        );

        if (null === $apiKey) {
            throw new CustomUserMessageAuthenticationException('Nieprawidłowy lub nieaktywny klucz API.');
        }

        return new SelfValidatingPassport(
            new UserBadge($apiKey->getId(), fn () => new ApiKeyUser($apiKey))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof ApiKeyUser) {
            $apiKey = $user->getApiKey();
            $apiKey->setLastUsedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new JsonResponse(['error' => $message], Response::HTTP_UNAUTHORIZED);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->headers->get('X-API-Key');
        if (is_string($header) && '' !== $header) {
            return $header;
        }

        $authorization = $request->headers->get('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
