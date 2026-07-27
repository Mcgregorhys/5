<?php

namespace App\Controller\Api;

use App\Attribute\RequireApiPermission;
use App\Entity\Product;
use App\Enum\ApiPermission;
use App\Enum\ColorsOption;
use App\Enum\ShippingOption;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ApiSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/products')]
class ProductApiController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiSerializer $apiSerializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_products_list', methods: ['GET'])]
    #[RequireApiPermission(ApiPermission::PRODUCTS_READ->value)]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findBy([], ['id' => 'ASC']);

        return $this->json([
            'data' => array_map(
                fn (Product $product) => $this->apiSerializer->serializeProduct($product),
                $products
            ),
            'total' => count($products),
        ]);
    }

    #[Route('/{id}', name: 'api_products_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RequireApiPermission(ApiPermission::PRODUCTS_READ->value)]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produkt nie znaleziony.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['data' => $this->apiSerializer->serializeProduct($product)]);
    }

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    #[RequireApiPermission(ApiPermission::PRODUCTS_WRITE->value)]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Nieprawidłowy format JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $this->applyProductData($product, $payload);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatValidationErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json(
            ['data' => $this->apiSerializer->serializeProduct($product)],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_products_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[RequireApiPermission(ApiPermission::PRODUCTS_WRITE->value)]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produkt nie znaleziony.'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Nieprawidłowy format JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $this->applyProductData($product, $payload, false);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatValidationErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return $this->json(['data' => $this->apiSerializer->serializeProduct($product)]);
    }

    #[Route('/{id}', name: 'api_products_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[RequireApiPermission(ApiPermission::PRODUCTS_WRITE->value)]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produkt nie znaleziony.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyProductData(Product $product, array $payload, bool $isCreate = true): void
    {
        if (isset($payload['lp'])) {
            $product->setLp((string) $payload['lp']);
        } elseif ($isCreate) {
            $product->setLp((string) ($this->productRepository->count([]) + 1));
        }

        if (isset($payload['kod'])) {
            $product->setKod((string) $payload['kod']);
        }

        if (isset($payload['nazwaProduktu'])) {
            $product->setNazwaProduktu((string) $payload['nazwaProduktu']);
        }

        if (array_key_exists('amount', $payload)) {
            $product->setAmount(null !== $payload['amount'] ? (string) $payload['amount'] : null);
        }

        if (isset($payload['cenaNetto'])) {
            $product->setCenaNetto((string) $payload['cenaNetto']);
        }

        if (isset($payload['vat'])) {
            $product->setVat((string) $payload['vat']);
        }

        if (isset($payload['cenaBrutto'])) {
            $product->setCenaBrutto((string) $payload['cenaBrutto']);
        } elseif (isset($payload['cenaNetto'], $payload['vat'])) {
            $netto = (float) $payload['cenaNetto'];
            $vat = (float) $payload['vat'];
            $product->setCenaBrutto((string) ($netto + ($netto * $vat / 100)));
        } elseif (!$isCreate && (isset($payload['cenaNetto']) || isset($payload['vat']))) {
            $netto = (float) ($product->getCenaNetto() ?? 0);
            $vat = (float) ($product->getVat() ?? 0);
            $product->setCenaBrutto((string) ($netto + ($netto * $vat / 100)));
        }

        if (isset($payload['value'])) {
            $product->setValue((string) $payload['value']);
        } elseif (!$isCreate && (isset($payload['cenaNetto']) || isset($payload['vat']) || isset($payload['amount']))) {
            $netto = (float) ($product->getCenaNetto() ?? 0);
            $vat = (float) ($product->getVat() ?? 0);
            $amount = (float) ($product->getAmount() ?? 0);
            $brutto = $netto + ($netto * $vat / 100);
            $product->setValue((string) ($brutto * $amount));
        }

        if (isset($payload['nettoMinus20'])) {
            $product->setNettoMinus20((string) $payload['nettoMinus20']);
        }

        if (isset($payload['nettoMinus30'])) {
            $product->setNettoMinus30((string) $payload['nettoMinus30']);
        }

        if (isset($payload['eurMinus20'])) {
            $product->setEurMinus20((string) $payload['eurMinus20']);
        }

        if (isset($payload['eurMinus30'])) {
            $product->setEurMinus30((string) $payload['eurMinus30']);
        }

        if (array_key_exists('shippingOption', $payload)) {
            $product->setShippingOption($payload['shippingOption']);
        }

        if (array_key_exists('colorsOption', $payload)) {
            $value = $payload['colorsOption'];
            $product->setColorsOption(
                is_string($value) && '' !== $value ? ColorsOption::from($value) : null
            );
        }

        if (array_key_exists('categoryId', $payload)) {
            if (null === $payload['categoryId']) {
                $product->setCategory(null);
            } else {
                $category = $this->categoryRepository->find((int) $payload['categoryId']);
                $product->setCategory($category);
            }
        }

        if ($isCreate) {
            $product->setNettoMinus20($product->getNettoMinus20() ?? '0');
            $product->setNettoMinus30($product->getNettoMinus30() ?? '0');
            $product->setEurMinus20($product->getEurMinus20() ?? '0');
            $product->setEurMinus30($product->getEurMinus30() ?? '0');
            $product->setShippingOption($product->getShippingOption() ?? ShippingOption::DOMESTIC);
        }
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
