<?php

namespace App\Security;

use App\Entity\ApiKey;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiKeyUser implements UserInterface
{
    public function __construct(
        private readonly ApiKey $apiKey,
    ) {
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function getOwner(): User
    {
        return $this->apiKey->getOwner();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->apiKey->hasPermission($permission);
    }

    public function getRoles(): array
    {
        return ['ROLE_API'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return 'api_key_' . $this->apiKey->getId();
    }
}
