<?php

namespace App\Service\PrestaShop;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;

class PrestaShopImageImporter
{
    public function __construct(
        private readonly PrestaShopClient $prestashopClient,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileUploader $fileUploader,
    ) {
    }

    /**
     * @return array{imported: int, skipped: int, unchanged: int, errors: list<string>, total: int}
     */
    public function importAll(bool $force = false): array
    {
        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'errors' => [],
            'total' => 0,
        ];

        $productIds = $this->productRepository->findIdsWithPrestashopId();
        $stats['total'] = count($productIds);

        foreach ($productIds as $productId) {
            try {
                $result = $this->importOneById($productId, $force);
                ++$stats[$result];
            } catch (\Throwable $e) {
                $stats['errors'][] = sprintf('Produkt #%d: %s', $productId, $e->getMessage());
            }

            $this->entityManager->clear();
        }

        return $stats;
    }

    /**
     * @return 'imported'|'skipped'|'unchanged'
     */
    public function importOneById(int $productId, bool $force = false): string
    {
        $product = $this->productRepository->find($productId);
        if (!$product instanceof Product || null === $product->getPrestashopId()) {
            return 'skipped';
        }

        return $this->importForProduct($product, $force);
    }

    /**
     * @return 'imported'|'skipped'|'unchanged'
     */
    public function importForProduct(Product $product, bool $force = false): string
    {
        $prestashopId = $product->getPrestashopId();
        if (null === $prestashopId) {
            return 'skipped';
        }

        if (!$force && null !== $product->getImageFilename()) {
            return 'unchanged';
        }

        $imageId = $this->prestashopClient->fetchDefaultImageId($prestashopId);
        if (null === $imageId) {
            return 'skipped';
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ps_img_');
        if (false === $tempPath) {
            throw new \RuntimeException('Nie można utworzyć pliku tymczasowego.');
        }

        try {
            $extension = $this->prestashopClient->downloadProductImageToFile($prestashopId, $imageId, $tempPath);
            $filename = $this->fileUploader->saveFromPath($tempPath, $extension);
        } finally {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        }

        $oldFilename = $product->getImageFilename();
        if (null !== $oldFilename && $oldFilename !== $filename) {
            $this->fileUploader->remove($oldFilename);
        }

        $product->setImageFilename($filename);
        $this->entityManager->flush();

        return 'imported';
    }
}
