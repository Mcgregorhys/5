<?php

namespace App\Enum;

enum ApiPermission: string
{
    case PRODUCTS_READ = 'products:read';
    case PRODUCTS_WRITE = 'products:write';
    case CATEGORIES_READ = 'categories:read';
    case CATEGORIES_WRITE = 'categories:write';

    public function label(): string
    {
        return match ($this) {
            self::PRODUCTS_READ => 'Odczyt produktów',
            self::PRODUCTS_WRITE => 'Zapis produktów',
            self::CATEGORIES_READ => 'Odczyt kategorii',
            self::CATEGORIES_WRITE => 'Zapis kategorii',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PRODUCTS_READ => 'GET /api/v1/products, GET /api/v1/products/{id}',
            self::PRODUCTS_WRITE => 'POST, PUT, DELETE /api/v1/products',
            self::CATEGORIES_READ => 'GET /api/v1/categories, GET /api/v1/categories/{id}',
            self::CATEGORIES_WRITE => 'POST, PUT, DELETE /api/v1/categories',
        };
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
