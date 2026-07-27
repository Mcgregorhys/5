<?php

namespace App\Command;

use App\Repository\ProductRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:images:compress',
    description: 'Kompresuje istniejące zdjęcia produktów (max 400px, JPEG 78%)',
)]
class CompressProductImagesCommand extends Command
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly FileUploader $fileUploader,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Kompresja zdjęć produktów');

        $products = $this->productRepository->createQueryBuilder('p')
            ->where('p.imageFilename IS NOT NULL')
            ->andWhere("p.imageFilename != ''")
            ->getQuery()
            ->getResult();

        $compressed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($products as $product) {
            $filename = $product->getImageFilename();
            $path = $this->fileUploader->getTargetDirectory() . '/' . $filename;

            if (!is_file($path)) {
                ++$skipped;
                continue;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            try {
                $newFilename = $this->fileUploader->saveFromPath($path, $extension);
                if ($newFilename !== $filename) {
                    $this->fileUploader->remove($filename);
                    $product->setImageFilename($newFilename);
                }
                ++$compressed;
            } catch (\Throwable $e) {
                $errors[] = sprintf('%s: %s', $filename, $e->getMessage());
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Skompresowano %d zdjęć, pominięto %d.', $compressed, $skipped));

        if ([] !== $errors) {
            $io->warning('Błędy:');
            $io->listing(array_slice($errors, 0, 10));
        }

        return Command::SUCCESS;
    }
}
