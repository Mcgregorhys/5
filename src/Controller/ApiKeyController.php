<?php

namespace App\Controller;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Enum\ApiPermission;
use App\Form\ApiKeyFormType;
use App\Form\ApiKeyPermissionsFormType;
use App\Repository\ApiKeyRepository;
use App\Service\ApiKeyManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api-keys')]
class ApiKeyController extends AbstractController
{
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ApiKeyManager $apiKeyManager,
    ) {
    }

    #[Route('', name: 'api_keys_index', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('api_key/index.html.twig', [
            'apiKeys' => $this->apiKeyRepository->findByOwner($user),
            'permissions' => ApiPermission::all(),
        ]);
    }

    #[Route('/new', name: 'api_keys_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ApiKeyFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $data = $form->getData();

            $result = $this->apiKeyManager->create(
                $user,
                $data['name'],
                $data['permissions'] ?? []
            );

            $this->addFlash('success', 'Klucz API został utworzony. Skopiuj go teraz — nie będzie już widoczny.');

            return $this->render('api_key/created.html.twig', [
                'apiKey' => $result['apiKey'],
                'plainToken' => $result['plainToken'],
            ]);
        }

        return $this->render('api_key/new.html.twig', [
            'form' => $form->createView(),
            'permissions' => ApiPermission::all(),
        ]);
    }

    #[Route('/{id}/edit', name: 'api_keys_edit', methods: ['GET', 'POST'])]
    public function edit(ApiKey $apiKey, Request $request): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGrantedToKey($apiKey);

        $form = $this->createForm(ApiKeyPermissionsFormType::class, [
            'permissions' => $apiKey->getPermissions(),
            'isActive' => $apiKey->isActive(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $apiKey->setIsActive((bool) ($data['isActive'] ?? false));
            $this->apiKeyManager->updatePermissions($apiKey, $data['permissions'] ?? []);

            $this->addFlash('success', 'Uprawnienia klucza API zostały zaktualizowane.');

            return $this->redirectToRoute('api_keys_index');
        }

        return $this->render('api_key/edit.html.twig', [
            'apiKey' => $apiKey,
            'form' => $form->createView(),
            'permissions' => ApiPermission::all(),
        ]);
    }

    #[Route('/{id}/revoke', name: 'api_keys_revoke', methods: ['POST'])]
    public function revoke(ApiKey $apiKey, Request $request): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGrantedToKey($apiKey);

        if ($this->isCsrfTokenValid('revoke' . $apiKey->getId(), (string) $request->request->get('_token'))) {
            $this->apiKeyManager->revoke($apiKey);
            $this->addFlash('success', 'Klucz API został dezaktywowany.');
        }

        return $this->redirectToRoute('api_keys_index');
    }

    #[Route('/{id}/delete', name: 'api_keys_delete', methods: ['POST'])]
    public function delete(ApiKey $apiKey, Request $request): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGrantedToKey($apiKey);

        if ($this->isCsrfTokenValid('delete' . $apiKey->getId(), (string) $request->request->get('_token'))) {
            $this->apiKeyManager->delete($apiKey);
            $this->addFlash('success', 'Klucz API został usunięty.');
        }

        return $this->redirectToRoute('api_keys_index');
    }

    private function denyAccessUnlessGrantedToKey(ApiKey $apiKey): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($apiKey->getOwner()?->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Nie masz dostępu do tego klucza API.');
        }
    }
}
