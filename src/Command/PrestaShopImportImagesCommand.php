<?php

namespace App\Command;

use App\Service\PrestaShop\PrestaShopImageImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:prestashop:import-images',
    description: 'Importuje zdjęcia produktów z PrestaShop przez REST API',
)]
class PrestaShopImportImagesCommand extends Command
{
    public function __construct(
        private readonly PrestaShopImageImporter $imageImporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Pobierz zdjęcia ponownie, nawet jeśli produkt już ma obraz');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '512M');

        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        $io->title('Import zdjęć produktów z PrestaShop');

        try {
            $stats = $this->imageImporter->importAll($force);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Import zdjęć zakończony: %d produktów z PrestaShop, zaimportowano %d, bez zmian %d, pominięto %d.',
            $stats['total'],
            $stats['imported'],
            $stats['unchanged'],
            $stats['skipped']
        ));

        if ([] !== $stats['errors']) {
            $io->warning('Błędy podczas importu zdjęć:');
            $io->listing(array_slice($stats['errors'], 0, 20));
        }

        return Command::SUCCESS;
    }
}
