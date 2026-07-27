<?php

namespace App\Command;

use App\Service\PrestaShop\PrestaShopProductImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:prestashop:import',
    description: 'Importuje produkty z PrestaShop (ps_product, ps_product_lang) przez REST API',
)]
class PrestaShopImportCommand extends Command
{
    public function __construct(
        private readonly PrestaShopProductImporter $importer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import produktów z PrestaShop');

        try {
            $stats = $this->importer->importAll();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Import zakończony: %d produktów w sklepie, utworzono %d, zaktualizowano %d, pominięto %d.',
            $stats['total'],
            $stats['created'],
            $stats['updated'],
            $stats['skipped']
        ));

        if ([] !== $stats['errors']) {
            $io->warning('Błędy podczas importu:');
            $io->listing($stats['errors']);
        }

        return Command::SUCCESS;
    }
}
