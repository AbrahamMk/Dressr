<?php

namespace App\Enums;

enum ItemFitType: string
{
    case Slim = 'slim';
    case Regular = 'regular';
    case Loose = 'loose';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
