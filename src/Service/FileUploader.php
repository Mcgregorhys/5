<?php 

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private string $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file): string
    {
        // jeśli plik to webp → konwersja do jpg
        if ($file->getClientOriginalExtension() === 'webp') {
            $image = imagecreatefromwebp($file->getPathname());
            $newFilename = uniqid().'.jpg';
            $newPath = $this->targetDirectory.'/'.$newFilename;

            imagejpeg($image, $newPath, 90);
            imagedestroy($image);

            return $newFilename;
        }

        // normalny zapis innych plików
        $filename = uniqid().'.'.$file->guessExtension();
        $file->move($this->targetDirectory, $filename);

        return $filename;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
