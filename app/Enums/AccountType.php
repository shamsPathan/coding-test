<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self Individual()
 * @method static self Business()
 */
class AccountType extends Enum
{
    protected static function values(): array
    {
        return [
            'Individual' => 'individual',
            'Business' => 'business',
        ];
    }

    public static function valuesArray(): array
    {
        return array_values(static::values());
    }
}
