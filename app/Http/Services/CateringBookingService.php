<?php

namespace App\Http\Services;

use App\Enums\Module;
use App\Models\Address;
use App\Models\CateringBooking;
use App\Models\CateringBookingItem;
use App\Models\CateringBookingSection;
use App\Models\CateringPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CateringBookingService
{

    public function createBooking(array $data, $user): CateringBooking
    {

        try {

            return DB::transaction(function () use ($data, $user) {
                $package = CateringPackage::query()
                    ->where('status', 1)
                    ->where(
                        'module_id',
                        Module::JAGDAI_CATERING
                    )
                    ->with([
                        'activeSections.activeItems.menuItem',
                    ])
                    ->find($data['package_id']);

                if (!$package) {

                    throw ValidationException::withMessages([
                        'package_id' => [
                            'The selected catering package is not available.',
                        ],
                    ]);
                }

                $address = Address::query()
                    ->where('id', $data['address_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$address) {

                    throw ValidationException::withMessages([
                        'address_id' => [
                            'The selected address does not belong to the current user.',
                        ],
                    ]);
                }

                if (blank($address->receiver_name)) {

                    throw ValidationException::withMessages([
                        'address_id' => [
                            'Receiver name is missing from the selected address.',
                        ],
                    ]);
                }

                if (blank($address->receiver_phone)) {

                    throw ValidationException::withMessages([
                        'address_id' => [
                            'Receiver phone is missing from the selected address.',
                        ],
                    ]);
                }

                $guestCount = (int) $data['guest_count'];

                if (
                    $guestCount < (int) $package->min_guests
                ) {

                    throw ValidationException::withMessages([
                        'guest_count' => [
                            "Minimum guests for this package are {$package->min_guests}.",
                        ],
                    ]);
                }

                if (
                    $package->max_guests !== null &&
                    $guestCount > (int) $package->max_guests
                ) {

                    throw ValidationException::withMessages([
                        'guest_count' => [
                            "Maximum guests for this package are {$package->max_guests}.",
                        ],
                    ]);
                }
                $submittedSections = collect(
                    $data['sections'] ?? []
                )->mapWithKeys(
                    function (array $section) {

                        return [
                            (int) $section['section_id'] => collect(
                                $section['items'] ?? []
                            )
                                ->map(fn($id) => (int) $id)
                                ->unique()
                                ->values(),
                        ];
                    }
                );

                $packageSections = $package->activeSections;

                $packageSectionIds = $packageSections
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                $submittedSectionIds = $submittedSections
                    ->keys()
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                $invalidSectionIds = array_diff(
                    $submittedSectionIds,
                    $packageSectionIds
                );

                if (!empty($invalidSectionIds)) {

                    throw ValidationException::withMessages([
                        'sections' => [
                            'One or more selected sections do not belong to this package.',
                        ],
                    ]);
                }

                $packagePrice = (float) $package->price;

                $packagePriceType = strtolower(
                    (string) $package->price_type
                );

                $packageTotal = in_array(
                    $packagePriceType,
                    [
                        'per_person',
                        'per_plate',
                    ],
                    true
                )
                    ? $packagePrice * $guestCount
                    : $packagePrice;

                $booking = CateringBooking::create([
                    'user_id' => $user->id,
                    'module_id' => $package->module_id,
                    'catering_package_id' =>
                    $package->id,
                    'address_id' =>
                    $address->id,

                    'package_name' =>
                    $package->name,

                    'package_price' =>
                    $package->price,

                    'package_price_type' =>
                    $package->price_type,

                    'guest_count' =>
                    $guestCount,

                    'event_date' =>
                    $data['event_date'],

                    'event_time' =>
                    $data['event_time'] ?? null,

                    'event_type' =>
                    $data['event_type'] ?? null,

                    'customer_name' =>
                    $address->receiver_name,

                    'customer_mobile' =>
                    $address->receiver_phone,

                    'customer_email' =>
                    $user->email,

                    'event_address' => json_encode([
                        'address' =>
                        $address->address,

                        'apartment' =>
                        $address->apartment,

                        'pincode' =>
                        $address->pincode,
                    ]),


                    'event_lat' =>
                    $address->latitude,

                    'event_long' =>
                    $address->longitude,

                    'special_instructions' =>
                    $data['special_instructions'] ?? null,

                    'sub_total' => 0,

                    'total_amount' => 0,

                    'status' => 10,
                ]);


                $additionalAmount = 0;

                foreach ($packageSections as $packageSection) {

                    $submittedItemIds = collect(
                        $submittedSections->get(
                            (int) $packageSection->id,
                            []
                        )
                    )
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values();
                    $availableItems =
                        $packageSection->activeItems;

                    $availableItemIds = $availableItems
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->values();

                    if (
                        $packageSection->selection_type === 'fixed'
                    ) {

                        $selectedItemIds =
                            $availableItemIds;
                    } else {


                        $invalidItemIds = $submittedItemIds
                            ->diff($availableItemIds)
                            ->values()
                            ->all();

                        if (!empty($invalidItemIds)) {

                            throw ValidationException::withMessages([
                                'sections' => [
                                    "One or more items do not belong to section '{$packageSection->name}'.",
                                ],
                            ]);
                        }

                        $selectedCount =
                            $submittedItemIds->count();

                        $minSelections =
                            (int) $packageSection->min_selections;

                        $maxSelections =
                            (int) $packageSection->max_selections;

                        if (
                            $selectedCount < $minSelections
                        ) {

                            throw ValidationException::withMessages([
                                'sections' => [
                                    "Section '{$packageSection->name}' requires at least {$minSelections} selection(s).",
                                ],
                            ]);
                        }

                        if (
                            $maxSelections > 0 &&
                            $selectedCount > $maxSelections
                        ) {

                            throw ValidationException::withMessages([
                                'sections' => [
                                    "Section '{$packageSection->name}' allows maximum {$maxSelections} selection(s).",
                                ],
                            ]);
                        }

                        $selectedItemIds =
                            $submittedItemIds;
                    }

                    $bookingSection =
                        CateringBookingSection::create([
                            'catering_booking_id' =>
                            $booking->id,

                            'catering_package_section_id' =>
                            $packageSection->id,

                            'name' =>
                            $packageSection->name,

                            'description' =>
                            $packageSection->description,

                            'selection_type' =>
                            $packageSection->selection_type,

                            'min_selections' =>
                            $packageSection->min_selections,

                            'max_selections' =>
                            $packageSection->max_selections,

                            'sort_order' =>
                            $packageSection->sort_order,
                        ]);
                    foreach ($availableItems as $packageItem) {

                        $menuItem =
                            $packageItem->menuItem;

                        if (!$menuItem) {
                            continue;
                        }

                        $isSelected =
                            $selectedItemIds->contains(
                                (int) $packageItem->id
                            );

                        $unitPrice =
                            (float) (
                                $menuItem->unit_price ?? 0
                            );

                        $discountPrice =
                            (float) (
                                $menuItem->discount_price ?? 0
                            );

                        $extraPrice =
                            (float) $packageItem->extra_price;

                        $finalUnitPrice = max(
                            0,
                            $unitPrice
                                - $discountPrice
                                + $extraPrice
                        );

                        $quantity = $isSelected
                            ? $guestCount
                            : 0;

                        $itemTotal = $isSelected
                            ? $extraPrice * $guestCount
                            : 0;

                        if ($isSelected) {
                            $additionalAmount += $itemTotal;
                        }

                        CateringBookingItem::create([
                            'catering_booking_section_id' =>
                            $bookingSection->id,

                            'catering_package_item_id' =>
                            $packageItem->id,

                            'menu_item_id' =>
                            $menuItem->id,

                            'menu_item_name' =>
                            $menuItem->name,

                            'menu_item_description' =>
                            $menuItem->description ?? null,

                            'unit_price' =>
                            $unitPrice,

                            'discount_price' =>
                            $discountPrice,

                            'extra_price' =>
                            $extraPrice,

                            'final_unit_price' =>
                            $finalUnitPrice,
                            'is_default' =>
                            (bool) $packageItem->is_default,

                            'is_selected' =>
                            $isSelected,

                            'quantity' =>
                            $quantity,

                            'item_total' =>
                            $itemTotal,

                            'sort_order' =>
                            $packageItem->sort_order,
                        ]);
                    }
                }

                $subTotal =
                    $packageTotal + $additionalAmount;

                $booking->update([
                    'sub_total' =>
                    $subTotal,

                    'total_amount' =>
                    $subTotal,
                ]);

                return $booking->fresh([
                    'package',
                    'sections.items',
                ]);
            });
        } catch (ValidationException $e) {

            throw $e;
        } catch (Throwable $e) {

            Log::error(
                'Catering booking creation failed.',
                [
                    'user_id' =>
                    $user?->id,

                    'package_id' =>
                    $data['package_id'] ?? null,

                    'address_id' =>
                    $data['address_id'] ?? null,

                    'exception' =>
                    $e->getMessage(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }

    public function getMyBookings(
        $user,
        int $perPage = 20
    ): LengthAwarePaginator {

        return CateringBooking::query()
            ->where('user_id', $user->id)
            ->with([
                'package',
                'sections.items',
            ])
            ->orderByDesc('id')
            ->paginate($perPage);
    }
    public function getBookingDetails(
        int $bookingId,
        $user
    ): CateringBooking {

        return CateringBooking::query()
            ->where('id', $bookingId)
            ->where('user_id', $user->id)
            ->with([
                'package',
                'sections.items',
            ])
            ->firstOrFail();
    }
}
