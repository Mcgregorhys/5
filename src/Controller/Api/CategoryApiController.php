<?php

namespace App\Controller\Api;

use App\Attribute\RequireApiPermission;
use App\Entity\Category;
use App\Enum\ApiPermission;
use App\Repository\CategoryRepository;
use App\Service\ApiSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/categories')]
class CategoryApiController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiSerializer $apiSerializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    #[RequireApiPermission(ApiPermission::CATEGORIES_READ->value)]
    public function list(): JsonResponse
    {
        $categories = $this->categoryRepository->findBy([], ['id' => 'ASC']);

        return $this->json([
            'data' => array_map(
                fn (Category $category) => $this->apiSerializer->serializeCategory($category),
                $categories
            ),
            'total' => count($categories),
        ]);
    }

    #[Route('/{id}', name: 'api_categories_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RequireApiPermission(ApiPermission::CATEGORIES_READ->value)]
    public function show(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return $this->json(['error' => 'Kategoria nie znaleziona.'], Response::HTTP_NOT_FOUND);
        }

        $withProducts = $request->query->getBoolean('withProducts');

        return $this->json([
            'data' => $this->apiSerializer->serializeCategory($category, $withProducts),
        ]);
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    #[RequireApiPermission(ApiPermission::CATEGORIES_WRITE->value)]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Nieprawidłowy format JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($payload['name']) || '' === trim((string) $payload['name'])) {
            return $this->json(['error' => 'Pole "name" jest wymagane.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category = new Category();
        $category->setNameOfCat(trim((string) $payload['name']));

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatValidationErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->json(
            ['data' => $this->apiSerializer->serializeCategory($category)],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_categories_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[RequireApiPermission(ApiPermission::CATEGORIES_WRITE->value)]
    public function update(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return $this->json(['error' => 'Kategoria nie znaleziona.'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Nieprawidłowy format JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($payload['name'])) {
            $category->setNameOfCat(trim((string) $payload['name']));
        }

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatValidationErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return $this->json(['data' => $this->apiSerializer->serializeCategory($category)]);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RequireApiPermission(ApiPermission::CATEGORIES_WRITE->value)]
    public function delete(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return $this->json(['error' => 'Kategoria nie znaleziona.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function formatValidationErrors(\Symfony\Component\Validator\ConstraintViolationListInterface $errors): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[$error->getPropertyPath()] = $error->getMessage();
        }

        return $formatted;
    }
}
