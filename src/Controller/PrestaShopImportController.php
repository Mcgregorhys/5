<?php

namespace App\Controller;

use App\Service\PrestaShop\PrestaShopImageImporter;
use App\Service\PrestaShop\PrestaShopProductImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/prestashop')]
#[IsGranted('ROLE_USER')]
class PrestaShopImportController extends AbstractController
{
    public function __construct(
        private readonly PrestaShopProductImporter $importer,
        private readonly PrestaShopImageImporter $imageImporter,
        private readonly string $prestashopUrl,
    ) {
    }

    #[Route('/import', name: 'prestashop_import', methods: ['POST'])]
    public function import(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('prestashop_import', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token CSRF.');

            return $this->redirectToRoute('product_list');
        }

        try {
            $stats = $this->importer->importAll();

            $this->addFlash('success', sprintf(
                'Import PrestaShop (%s): %d produktów w sklepie — utworzono %d, zaktualizowano %d, pominięto %d.',
                $this->prestashopUrl,
                $stats['total'],
                $stats['created'],
                $stats['updated'],
                $stats['skipped']
            ));

            if ([] !== $stats['errors']) {
                $this->addFlash('warning', 'Część produktów nie została zaimportowana: ' . implode('; ', array_slice($stats['errors'], 0, 3)));
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Błąd importu PrestaShop: ' . $e->getMessage());
        }

        return $this->redirectToRoute('product_list');
    }

    #[Route('/import-images', name: 'prestashop_import_images', methods: ['POST'])]
    public function importImages(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('prestashop_import_images', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Nieprawidłowy token CSRF.');

            return $this->redirectToRoute('product_list');
        }

        $force = $request->request->getBoolean('force');

        try {
            $stats = $this->imageImporter->importAll($force);

            $this->addFlash('success', sprintf(
                'Import zdjęć PrestaShop (%s): %d produktów — zaimportowano %d, bez zmian %d, pominięto %d.',
                $this->prestashopUrl,
                $stats['total'],
                $stats['imported'],
                $stats['unchanged'],
                $stats['skipped']
            ));

            if ([] !== $stats['errors']) {
                $this->addFlash('warning', 'Część zdjęć nie została zaimportowana: ' . implode('; ', array_slice($stats['errors'], 0, 3)));
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Błąd importu zdjęć PrestaShop: ' . $e->getMessage());
        }

        return $this->redirectToRoute('product_list');
    }
}
