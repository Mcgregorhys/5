<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;

class ApiSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serializeProduct(Product $product): array
    {
        $category = $product->getCategory();

        return [
            'id' => $product->getId(),
            'prestashopId' => $product->getPrestashopId(),
            'lp' => $product->getLp(),
            'kod' => $product->getKod(),
            'nazwaProduktu' => $product->getNazwaProduktu(),
            'amount' => $product->getAmount(),
            'cenaNetto' => $product->getCenaNetto(),
            'vat' => $product->getVat(),
            'cenaBrutto' => $product->getCenaBrutto(),
            'value' => $product->getValue(),
            'nettoMinus20' => $product->getNettoMinus20(),
            'nettoMinus30' => $product->getNettoMinus30(),
            'eurMinus20' => $product->getEurMinus20(),
            'eurMinus30' => $product->getEurMinus30(),
            'imageFilename' => $product->getImageFilename(),
            'shippingOption' => $product->getShippingOption()?->value,
            'colorsOption' => $product->getColorsOption()?->value,
            'category' => $category ? [
                'id' => $category->getId(),
                'name' => $category->getNameOfCat(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeCategory(Category $category, bool $withProducts = false): array
    {
        $data = [
            'id' => $category->getId(),
            'name' => $category->getNameOfCat(),
            'productsCount' => $category->getProducts()->count(),
        ];

        if ($withProducts) {
            $data['products'] = array_map(
                fn (Product $product) => $this->serializeProduct($product),
                $category->getProducts()->toArray()
            );
        }

        return $data;
    }
}
