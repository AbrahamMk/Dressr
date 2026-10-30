<?php

namespace App\Enums;

enum OutfitSource: string
{
    case Rules = 'rules';
    case Ai = 'ai';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
