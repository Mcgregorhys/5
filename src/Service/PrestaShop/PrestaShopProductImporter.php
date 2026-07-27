<?php

namespace App\Service\PrestaShop;

use App\Entity\Product;
use App\Enum\ShippingOption;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class PrestaShopProductImporter
{
    private int $batchSize = 50;

    public function __construct(
        private readonly PrestaShopClient $prestashopClient,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>, total: int}
     */
    public function importAll(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'total' => 0,
        ];

        $ids = $this->prestashopClient->fetchProductIds();
        $stats['total'] = count($ids);
        $processed = 0;

        foreach ($ids as $prestashopId) {
            try {
                $result = $this->importOne($prestashopId, false);
                ++$stats[$result];
            } catch (\Throwable $e) {
                $stats['errors'][] = sprintf('ID %d: %s', $prestashopId, $e->getMessage());
            }

            ++$processed;
            if (0 === $processed % $this->batchSize) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        return $stats;
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    public function importOne(int $prestashopId, bool $flush = true): string
    {
        $data = $this->prestashopClient->fetchProductData($prestashopId);
        if (null === $data) {
            return 'skipped';
        }

        $product = $this->productRepository->findOneBy(['prestashopId' => $prestashopId])
            ?? $this->findByKod($data['reference'])
            ?? new Product();

        $isNew = null === $product->getId();

        $product->setPrestashopId($data['id_product']);
        $product->setKod($this->resolveKod($data['reference'], $data['id_product']));
        $product->setNazwaProduktu($this->resolveName($data['name'], $data['id_product']));
        $product->setCenaNetto($this->formatPrice($data['price']));

        $this->applyDefaults($product, $isNew);

        if ($isNew) {
            $this->entityManager->persist($product);
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return $isNew ? 'created' : 'updated';
    }

    private function findByKod(string $reference): ?Product
    {
        if ('' === $reference) {
            return null;
        }

        return $this->productRepository->findOneBy(['kod' => $reference]);
    }

    private function resolveKod(string $reference, int $prestashopId): string
    {
        if ('' !== $reference) {
            return substr($reference, 0, 20);
        }

        return 'PS' . $prestashopId;
    }

    private function resolveName(string $name, int $prestashopId): string
    {
        if ('' !== $name) {
            return substr($name, 0, 255);
        }

        return 'Produkt PrestaShop #' . $prestashopId;
    }

    private function formatPrice(string $price): string
    {
        return number_format((float) $price, 2, '.', '');
    }

    private function applyDefaults(Product $product, bool $isNew): void
    {
        $vat = $product->getVat() ?? '23';
        $product->setVat($vat);

        $netto = (float) $product->getCenaNetto();
        $brutto = $netto + ($netto * (float) $vat / 100);
        $product->setCenaBrutto(number_format($brutto, 2, '.', ''));

        $amount = $product->getAmount() ?? '0';
        $product->setAmount($amount);
        $product->setValue(number_format($brutto * (float) $amount, 2, '.', ''));

        $product->setNettoMinus20(number_format($netto * 0.8, 2, '.', ''));
        $product->setNettoMinus30(number_format($netto * 0.7, 2, '.', ''));
        $product->setEurMinus20(number_format($brutto * 0.8, 2, '.', ''));
        $product->setEurMinus30(number_format($brutto * 0.7, 2, '.', ''));

        if ($isNew) {
            $product->setLp((string) ($this->productRepository->count([]) + 1));
            $product->setShippingOption(ShippingOption::DOMESTIC);
        }
    }
}
