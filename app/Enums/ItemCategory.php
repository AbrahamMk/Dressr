<?php

namespace App\Enums;

enum ItemCategory: string
{
    case Top = 'top';
    case Bottom = 'bottom';
    case Dress = 'dress';
    case Outerwear = 'outerwear';
    case Shoes = 'shoes';
    case Accessory = 'accessory';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
