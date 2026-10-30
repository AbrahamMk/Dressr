<?php

namespace App\Enums;

enum ItemStretch: string
{
    case None = 'none';
    case Some = 'some';
    case High = 'high';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
