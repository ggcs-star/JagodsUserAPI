<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Http\Resources\v1\CateringPackageResource;
use App\Http\Resources\v1\CateringPackageSectionResource;
use App\Http\Services\CateringPackageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Resources\v1\CateringPackageSectionDetailResource;

class CateringPackageController extends BackendController
{
    use ApiResponse;

    public function __construct(
        protected CateringPackageService $cateringPackageService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $packages = $this->cateringPackageService->getPackages(
            perPage: (int) ($validated['per_page'] ?? 20)
        );

        return $this->successResponse(
            message: 'Catering packages retrieved successfully.',
            data: CateringPackageResource::collection($packages)
        );
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'id' => [
                'required',
                'integer',
                'exists:catering_packages,id',
            ],
        ]);

        $package = $this->cateringPackageService->getPackageDetails(
            (int) $validated['id']
        );

        return $this->successResponse(
            message: 'Catering package details retrieved successfully.',
            data: new CateringPackageResource($package)
        );
    }

    public function sections(Request $request)
    {
        $validated = $request->validate([
            'package_id' => [
                'required',
                'integer',
                'exists:catering_packages,id',
            ],
        ]);

        $sections = $this->cateringPackageService->getPackageSections(
            (int) $validated['package_id']
        );

        return $this->successResponse(
            message: 'Catering package sections retrieved successfully.',
            data: CateringPackageSectionResource::collection($sections)
        );
    }

    public function sectionDetails(Request $request)
    {
        $validated = $request->validate([
            'section_id' => [
                'required',
                'integer',
                'exists:catering_package_sections,id',
            ],
        ]);

        $section = $this->cateringPackageService->getSectionDetails(
            (int) $validated['section_id']
        );

        return $this->successResponse(
            message: 'Catering package section details retrieved successfully.',
            data: new CateringPackageSectionDetailResource($section)
        );
    }
}
