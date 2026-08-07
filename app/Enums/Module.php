<?php

namespace App\Enums;

class Module
{
    const YOUR_CITY = 1;
    const ALL_OVER_INDIA = 2;

    const YOUR_CITY_SLUG = 'your_city';
    const ALL_OVER_INDIA_SLUG = 'all_over_india';

    public static function all(): array
    {
        return [
            self::YOUR_CITY => self::YOUR_CITY_SLUG,
            self::ALL_OVER_INDIA => self::ALL_OVER_INDIA_SLUG,
        ];
    }

    public static function pickupAllowed(string $slug): bool
    {
        return $slug !== self::ALL_OVER_INDIA_SLUG;
    }
}