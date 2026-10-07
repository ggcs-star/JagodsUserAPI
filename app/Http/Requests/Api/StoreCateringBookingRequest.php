<?php

namespace App\Http\Requests\Api;

use App\Enums\Module;
use App\Models\Address;
use App\Models\CateringPackage;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreCateringBookingRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => [
                'required',
                'integer',
                Rule::exists('catering_packages', 'id')
                    ->where(function ($query) {
                        $query
                            ->where('status', 1)
                            ->where(
                                'module_id',
                                Module::JAGDAI_CATERING
                            );
                    }),
            ],

            'guest_count' => [
                'required',
                'integer',
                'min:1',
            ],

            'event_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'event_time' => [
                'nullable',
                'date_format:H:i:s',
            ],

            'event_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')
                    ->where(function ($query) {
                        $query->where(
                            'user_id',
                            auth('api')->id()
                        );
                    }),
            ],

            'special_instructions' => [
                'nullable',
                'string',
            ],

            'sections' => [
                'nullable',
                'array',
            ],

            'sections.*.section_id' => [
                'required',
                'integer',
                'distinct',
            ],

            'sections.*.items' => [
                'nullable',
                'array',
            ],

            'sections.*.items.*' => [
                'required',
                'integer',
                'distinct',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {


            if ($validator->errors()->has('package_id')) {
                return;
            }

            $package = CateringPackage::query()
                ->where('status', 1)
                ->where(
                    'module_id',
                    Module::JAGDAI_CATERING
                )
                ->with([
                    'activeSections.activeItems',
                ])
                ->find($this->input('package_id'));

            if (!$package) {
                return;
            }

            $guestCount = (int) $this->input('guest_count');

            if (
                $guestCount < (int) $package->min_guests
            ) {
                $validator->errors()->add(
                    'guest_count',
                    "Minimum guests for this package are {$package->min_guests}."
                );
            }

            if (
                $package->max_guests !== null &&
                $guestCount > (int) $package->max_guests
            ) {
                $validator->errors()->add(
                    'guest_count',
                    "Maximum guests for this package are {$package->max_guests}."
                );
            }
            $address = Address::query()
                ->where(
                    'id',
                    $this->input('address_id')
                )
                ->where(
                    'user_id',
                    auth('api')->id()
                )
                ->first();

            if ($address) {
                if (blank($address->receiver_name)) {
                    $validator->errors()->add(
                        'address_id',
                        'Receiver name is missing from the selected address.'
                    );
                }

                if (blank($address->receiver_phone)) {
                    $validator->errors()->add(
                        'address_id',
                        'Receiver phone is missing from the selected address.'
                    );
                }
            }

            $submittedSections = collect(
                $this->input('sections', [])
            );

            foreach (
                $submittedSections as $sectionIndex => $submittedSection
            ) {

                $sectionId = (int) (
                    $submittedSection['section_id'] ?? 0
                );

                $packageSection = $package->activeSections
                    ->firstWhere('id', $sectionId);

                if (!$packageSection) {

                    $validator->errors()->add(
                        "sections.$sectionIndex.section_id",
                        'The selected section does not belong to this package.'
                    );

                    continue;
                }

                $submittedItems = collect(
                    $submittedSection['items'] ?? []
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

                $invalidItemIds = $submittedItems
                    ->diff($availableItemIds)
                    ->values();

                if ($invalidItemIds->isNotEmpty()) {

                    $validator->errors()->add(
                        "sections.$sectionIndex.items",
                        'One or more selected items do not belong to this section.'
                    );

                    continue;
                }

                if (
                    $packageSection->selection_type === 'fixed'
                ) {
                    continue;
                }

                $selectedCount = $submittedItems->count();

                $minSelections = (int) (
                    $packageSection->min_selections ?? 0
                );

                $maxSelections = (int) (
                    $packageSection->max_selections ?? 0
                );

                if (
                    $selectedCount < $minSelections
                ) {
                    $validator->errors()->add(
                        "sections.$sectionIndex.items",
                        "Section '{$packageSection->name}' requires at least {$minSelections} selection(s)."
                    );
                }

                if (
                    $maxSelections > 0 &&
                    $selectedCount > $maxSelections
                ) {
                    $validator->errors()->add(
                        "sections.$sectionIndex.items",
                        "Section '{$packageSection->name}' allows maximum {$maxSelections} selection(s)."
                    );
                }
            }

            foreach (
                $package->activeSections as $packageSection
            ) {

                if (
                    $packageSection->selection_type !== 'custom'
                ) {
                    continue;
                }

                $exists = $submittedSections->contains(
                    function ($submittedSection) use ($packageSection) {

                        return (int) (
                            $submittedSection['section_id'] ?? 0
                        ) === (int) $packageSection->id;
                    }
                );

                if (!$exists) {

                    $minSelections = (int) (
                        $packageSection->min_selections ?? 0
                    );

                    if ($minSelections > 0) {
                        $validator->errors()->add(
                            'sections',
                            "Please select items for section '{$packageSection->name}'."
                        );
                    }
                }
            }
        });
    }

    protected function failedValidation(
        Validator $validator
    ): void {
        throw new HttpResponseException(
            $this->validationResponse(
                $validator->errors()->toArray()
            )
        );
    }
}
