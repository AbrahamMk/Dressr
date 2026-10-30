<?php

namespace App\Enums;

enum ItemPattern: string
{
    case Solid = 'solid';
    case Striped = 'striped';
    case Plaid = 'plaid';
    case Floral = 'floral';
    case Graphic = 'graphic';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
