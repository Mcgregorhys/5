<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private const MAX_IMAGE_WIDTH = 100;
    private const MAX_IMAGE_HEIGHT = 100;
    private const JPEG_QUALITY = 78;

    private string $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $image = $this->createImageFromFile($file->getPathname(), $extension);
            if (false !== $image) {
                try {
                    return $this->saveCompressedImage($image, '');
                } finally {
                    imagedestroy($image);
                }
            }
        }

        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($this->targetDirectory, $filename);

        return $filename;
    }

    public function saveFromPath(string $sourcePath, string $extension = 'jpg'): string
    {
        $image = $this->createImageFromFile($sourcePath, $extension);
        if (false === $image) {
            throw new \RuntimeException('Nie można przetworzyć pobranego zdjęcia.');
        }

        try {
            return $this->saveCompressedImage($image, 'ps_');
        } finally {
            imagedestroy($image);
        }
    }

    public function saveFromBinary(string $content, string $extension = 'jpg'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        if (false === $tmp) {
            throw new \RuntimeException('Nie można utworzyć pliku tymczasowego.');
        }

        try {
            file_put_contents($tmp, $content);

            return $this->saveFromPath($tmp, $extension);
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
    }

    public function remove(string $filename): void
    {
        $path = $this->targetDirectory . '/' . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * @return \GdImage|false
     */
    private function createImageFromFile(string $path, string $extension)
    {
        $extension = strtolower($extension);

        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => @imagecreatefromstring((string) file_get_contents($path)),
        };

        if (false === $image) {
            $image = @imagecreatefromstring((string) file_get_contents($path));
        }

        return $image;
    }

    private function saveCompressedImage(\GdImage $source, string $prefix): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        [$newWidth, $newHeight] = $this->calculateDimensions($width, $height);

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $filename = uniqid($prefix) . '.jpg';
        $path = $this->targetDirectory . '/' . $filename;
        imagejpeg($canvas, $path, self::JPEG_QUALITY);
        imagedestroy($canvas);

        return $filename;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function calculateDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_IMAGE_WIDTH && $height <= self::MAX_IMAGE_HEIGHT) {
            return [$width, $height];
        }

        $ratio = min(self::MAX_IMAGE_WIDTH / $width, self::MAX_IMAGE_HEIGHT / $height);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }
}
