<?php
namespace App\Enum;

enum ColorsOption: string
{
    case WHITE = 'biały';
    case BLACK = 'czarny';

    case YELLOW = 'żółty';

    public function label(): string
    {
        return match ($this) {
            self::WHITE => 'Biały',
            self::BLACK => 'Czarny',
            self::YELLOW => 'Żółty',
        };
    }
}
