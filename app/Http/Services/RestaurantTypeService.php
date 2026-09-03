<?php

namespace App\Http\Services;

use Illuminate\Database\Eloquent\Builder;

class RestaurantTypeService
{
    public function getAllowedTypes(?string $type): ?array
    {
        if (blank($type)) {
            return null;
        }

        return match (strtolower(trim($type))) {

            'veg' => [
                'veg',
                'veg-and-non-veg',
            ],

            'pure_veg' => [
                'veg',
            ],

            'non_veg' => [
                'non-veg',
                'veg-and-non-veg',
            ],

            default => null,
        };
    }

    public function getHeaderType($request): ?string
    {
        $type = $request->header('restro_type');

        return blank($type)
            ? null
            : strtolower(trim($type));
    }

    public function apply(
        Builder $query,
        ?string $type,
        string $column = 'restroType'
    ): Builder {
        $allowedTypes = $this->getAllowedTypes($type);

        if ($allowedTypes === null) {
            return $query;
        }

        return $query->whereIn(
            $column,
            $allowedTypes
        );
    }

public function shouldHideNonVeg(?string $type): bool
{
    return in_array(
        strtolower(trim((string) $type)),
        ['veg', 'pure_veg'],
        true
    );
}
}