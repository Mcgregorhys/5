<?php
namespace App\Enum;

enum ShippingOption: string
{
    case DOMESTIC = 'transport_krajowy';
    case INTERNATIONAL = 'transport_zagraniczny';
    case EXPRESS = 'ekspresowa_dostawa';

    case UNKNOWN = 'unknown';

    public function getLabel(): string
    {
        return match($this) {
            self::DOMESTIC => 'Transport krajowy',
            self::INTERNATIONAL => 'Transport zagraniczny',
            self::EXPRESS => 'Ekspresowa dostawa',
            self::class => 'Nieznany typ transportu',
        };
    }
    public static function safeFrom($value): ?self
{
    if ($value instanceof self) {
        return $value;
    }
    return is_string($value) ? self::tryFrom($value) : null;
}
 }