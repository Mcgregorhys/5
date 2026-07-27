<?php

namespace App\Service;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Enum\ApiPermission;
use Doctrine\ORM\EntityManagerInterface;

class ApiKeyManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<string> $permissions
     *
     * @return array{apiKey: ApiKey, plainToken: string}
     */
    public function create(User $owner, string $name, array $permissions): array
    {
        $plainToken = $this->generatePlainToken();
        $validPermissions = $this->filterValidPermissions($permissions);

        $apiKey = new ApiKey();
        $apiKey
            ->setName($name)
            ->setOwner($owner)
            ->setPermissions($validPermissions)
            ->setTokenHash($this->hashToken($plainToken))
            ->setTokenPrefix(substr($plainToken, 0, 12));

        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();

        return ['apiKey' => $apiKey, 'plainToken' => $plainToken];
    }

    public function updatePermissions(ApiKey $apiKey, array $permissions): void
    {
        $apiKey->setPermissions($this->filterValidPermissions($permissions));
        $this->entityManager->flush();
    }

    public function revoke(ApiKey $apiKey): void
    {
        $apiKey->setIsActive(false);
        $this->entityManager->flush();
    }

    public function delete(ApiKey $apiKey): void
    {
        $this->entityManager->remove($apiKey);
        $this->entityManager->flush();
    }

    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function generatePlainToken(): string
    {
        return 'sk_' . bin2hex(random_bytes(32));
    }

    /**
     * @param list<string> $permissions
     *
     * @return list<string>
     */
    private function filterValidPermissions(array $permissions): array
    {
        $valid = array_map(static fn (ApiPermission $p) => $p->value, ApiPermission::all());

        return array_values(array_intersect($permissions, $valid));
    }
}
