<?php

namespace App\Service\PrestaShop;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PrestaShopClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $prestashopUrl,
        private readonly string $prestashopApiKey,
        private readonly int $prestashopLangId,
    ) {
    }

    /**
     * @return list<int>
     */
    public function fetchProductIds(): array
    {
        $data = $this->request('/api/products');

        $items = $data['products'] ?? [];
        $ids = [];

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return array{id_product: int, reference: string, price: string, name: string}|null
     */
    public function fetchProductData(int $prestashopId): ?array
    {
        $data = $this->request(sprintf(
            '/api/products/%d?language=%d',
            $prestashopId,
            $this->prestashopLangId
        ));

        $product = $data['product'] ?? null;
        if (!is_array($product)) {
            return null;
        }

        $id = (int) ($product['id'] ?? $prestashopId);
        if ($id <= 0) {
            return null;
        }

        return [
            'id_product' => $id,
            'reference' => trim((string) ($product['reference'] ?? '')),
            'price' => (string) ($product['price'] ?? '0'),
            'name' => $this->extractName($product['name'] ?? ''),
        ];
    }

    private function extractName(mixed $nameField): string
    {
        if (is_string($nameField)) {
            return trim($nameField);
        }

        if (!is_array($nameField)) {
            return '';
        }

        foreach ($nameField as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $langId = (int) ($entry['id'] ?? $entry['@attributes']['id'] ?? 0);
            $value = trim((string) ($entry['value'] ?? $entry['_content'] ?? ''));

            if ($langId === $this->prestashopLangId && '' !== $value) {
                return $value;
            }
        }

        foreach ($nameField as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $value = trim((string) ($entry['value'] ?? $entry['_content'] ?? ''));
            if ('' !== $value) {
                return $value;
            }
        }

        if (isset($nameField['language'])) {
            $language = $nameField['language'];
            if (is_array($language)) {
                return trim((string) ($language['_content'] ?? $language['value'] ?? ''));
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $path): array
    {
        if ('' === $this->prestashopUrl || '' === $this->prestashopApiKey) {
            throw new \RuntimeException('Skonfiguruj PRESTASHOP_URL i PRESTASHOP_API_KEY w pliku .env.local');
        }

        $url = rtrim($this->prestashopUrl, '/') . $path;
        $separator = str_contains($path, '?') ? '&' : '?';
        $url .= $separator . 'output_format=JSON';

        try {
            $response = $this->httpClient->request('GET', $url, [
                'auth_basic' => [$this->prestashopApiKey, ''],
                'headers' => [
                    'Accept' => 'application/json',
                    'Output-Format' => 'JSON',
                ],
                'timeout' => 60,
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            throw new \RuntimeException(sprintf(
                'Nie można połączyć z PrestaShop pod adresem %s. '
                . 'Upewnij się, że PRESTASHOP_URL wskazuje na sklep PrestaShop (nie na tę aplikację Symfony). '
                . 'Z kontenera Docker użyj np. http://host.docker.internal:PORT zamiast localhost. '
                . 'Błąd: %s',
                $this->prestashopUrl,
                $e->getMessage()
            ), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf(
                'PrestaShop API błąd HTTP %d dla %s: %s',
                $statusCode,
                $url,
                $response->getContent(false)
            ));
        }

        $content = $response->getContent();
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('PrestaShop API zwróciło nieprawidłowy JSON.');
        }

        return $decoded;
    }

    public function fetchDefaultImageId(int $prestashopId): ?int
    {
        $data = $this->request(sprintf(
            '/api/products/%d?language=%d',
            $prestashopId,
            $this->prestashopLangId
        ));

        $product = $data['product'] ?? null;
        if (!is_array($product)) {
            return null;
        }

        $defaultImageId = (int) ($product['id_default_image'] ?? 0);
        if ($defaultImageId > 0) {
            return $defaultImageId;
        }

        $images = $product['associations']['images'] ?? [];
        if (!is_array($images)) {
            return null;
        }

        foreach ($images as $image) {
            $imageId = (int) ($image['id'] ?? 0);
            if ($imageId > 0) {
                return $imageId;
            }
        }

        return null;
    }

    /**
     * @return array{content: string, extension: string}
     */
    public function downloadProductImage(int $prestashopId, int $imageId): array
    {
        if ('' === $this->prestashopUrl || '' === $this->prestashopApiKey) {
            throw new \RuntimeException('Skonfiguruj PRESTASHOP_URL i PRESTASHOP_API_KEY w pliku .env.local');
        }

        $url = rtrim($this->prestashopUrl, '/') . sprintf('/api/images/products/%d/%d', $prestashopId, $imageId);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'auth_basic' => [$this->prestashopApiKey, ''],
                'timeout' => 60,
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            throw new \RuntimeException(sprintf(
                'Nie można pobrać zdjęcia produktu %d: %s',
                $prestashopId,
                $e->getMessage()
            ), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf(
                'PrestaShop API błąd HTTP %d przy pobieraniu zdjęcia produktu %d',
                $statusCode,
                $prestashopId
            ));
        }

        $contentType = strtolower($response->getHeaders(false)['content-type'][0] ?? '');
        if (str_contains($contentType, 'json') || str_contains($contentType, 'xml') || str_contains($contentType, 'html')) {
            throw new \RuntimeException(sprintf('Brak pliku zdjęcia dla produktu PrestaShop %d', $prestashopId));
        }

        $content = $response->getContent();

        return [
            'content' => $content,
            'extension' => $this->resolveImageExtension($contentType, $content),
        ];
    }

    public function downloadProductImageToFile(int $prestashopId, int $imageId, string $targetPath): string
    {
        if ('' === $this->prestashopUrl || '' === $this->prestashopApiKey) {
            throw new \RuntimeException('Skonfiguruj PRESTASHOP_URL i PRESTASHOP_API_KEY w pliku .env.local');
        }

        $url = rtrim($this->prestashopUrl, '/') . sprintf('/api/images/products/%d/%d', $prestashopId, $imageId);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'auth_basic' => [$this->prestashopApiKey, ''],
                'timeout' => 60,
                'buffer' => false,
            ]);
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            throw new \RuntimeException(sprintf(
                'Nie można pobrać zdjęcia produktu %d: %s',
                $prestashopId,
                $e->getMessage()
            ), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf(
                'PrestaShop API błąd HTTP %d przy pobieraniu zdjęcia produktu %d',
                $statusCode,
                $prestashopId
            ));
        }

        $contentType = strtolower($response->getHeaders(false)['content-type'][0] ?? '');
        if (str_contains($contentType, 'json') || str_contains($contentType, 'xml') || str_contains($contentType, 'html')) {
            throw new \RuntimeException(sprintf('Brak pliku zdjęcia dla produktu PrestaShop %d', $prestashopId));
        }

        $handle = fopen($targetPath, 'w');
        if (false === $handle) {
            throw new \RuntimeException('Nie można zapisać pliku tymczasowego zdjęcia.');
        }

        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($handle, $chunk->getContent());
        }

        fclose($handle);

        $header = file_get_contents($targetPath, false, null, 0, 16) ?: '';

        return $this->resolveImageExtension($contentType, $header);
    }

    private function resolveImageExtension(string $contentType, string $content): string
    {
        if (str_contains($contentType, 'png')) {
            return 'png';
        }
        if (str_contains($contentType, 'webp')) {
            return 'webp';
        }
        if (str_contains($contentType, 'gif')) {
            return 'gif';
        }
        if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
            return 'jpg';
        }

        if (str_starts_with($content, "\x89PNG")) {
            return 'png';
        }
        if (str_starts_with($content, 'GIF')) {
            return 'gif';
        }
        if (str_starts_with($content, 'RIFF') && str_contains(substr($content, 0, 16), 'WEBP')) {
            return 'webp';
        }

        return 'jpg';
    }
}
