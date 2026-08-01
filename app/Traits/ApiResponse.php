<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * =====================================================
     * Success Response
     * =====================================================
     * Used For:
     * - Success API Response
     * - List API
     * - Details API
     * - Create/Update/Delete Response
     * =====================================================
     */
    public function successResponse(
        string $message = 'Success',
        mixed $data = [],
        int $statusCode = Response::HTTP_OK,
        ?array $pagination = null,
        ?array $meta = null
    ): JsonResponse {

        if ($data === null) {
            $data = [];
        }

        $response = [
            'status' => true,
            'success' => true,
            'status_code' => $statusCode,
            'errors' => [],
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($pagination)) {
            $response['pagination'] = $pagination;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * =====================================================
     * Error Response
     * =====================================================
     * Used For:
     * - Validation Error
     * - Business Logic Error
     * - Exception
     * =====================================================
     */
    public function errorResponse(
        string $message = 'Something went wrong',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        mixed $data = []
    ): JsonResponse {

        if ($data === null) {
            $data = [];
        }

        return response()->json([
            'status' => false,
            'success' => false,
            'status_code' => $statusCode,
            'errors' => $errors,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * =====================================================
     * Validation Error Response
     * =====================================================
     */
    public function validationResponse(array $errors): JsonResponse
    {
        return $this->errorResponse(
            message: 'Validation Error',
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors
        );
    }

    /**
     * =====================================================
     * Unauthorized Response
     * =====================================================
     */
    public function unauthorizedResponse(
        string $message = 'Unauthorized access'
    ): JsonResponse {

        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_UNAUTHORIZED,
            errors: [
                [
                    'field' => 'authorization',
                    'message' => $message,
                ]
            ]
        );
    }

    /**
     * =====================================================
     * Forbidden Response
     * =====================================================
     */
    public function forbiddenResponse(
        string $message = 'Access Forbidden'
    ): JsonResponse {

        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_FORBIDDEN,
            errors: [
                [
                    'field' => 'permission',
                    'message' => $message,
                ]
            ]
        );
    }

    /**
     * =====================================================
     * Not Found Response
     * =====================================================
     */
    public function notFoundResponse(
        string $message = 'Record Not Found'
    ): JsonResponse {

        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_NOT_FOUND,
            errors: [
                [
                    'field' => 'resource',
                    'message' => $message,
                ]
            ]
        );
    }

    /**
     * =====================================================
     * Created Response
     * =====================================================
     */
    public function createdResponse(
        string $message = 'Created Successfully',
        mixed $data = []
    ): JsonResponse {

        return $this->successResponse(
            message: $message,
            data: $data,
            statusCode: Response::HTTP_CREATED
        );
    }

    /**
     * =====================================================
     * Updated Response
     * =====================================================
     */
    public function updatedResponse(
        string $message = 'Updated Successfully',
        mixed $data = []
    ): JsonResponse {

        return $this->successResponse(
            message: $message,
            data: $data
        );
    }

    /**
     * =====================================================
     * Deleted Response
     * =====================================================
     */
    public function deletedResponse(
        string $message = 'Deleted Successfully'
    ): JsonResponse {

        return $this->successResponse(
            message: $message,
            data: []
        );
    }

    /**
     * =====================================================
     * No Content Response
     * =====================================================
     */
    public function noContentResponse(): JsonResponse
    {
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
    public function serverErrorResponse(
        string $message = 'Internal Server Error',
        array $errors = [],
        mixed $data = []
    ): JsonResponse {

        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
            errors: $errors,
            data: $data
        );
    }

    public function tooManyRequestsResponse(
        string $message = 'Too many attempts. Please try again later.',
        int $retryAfter = 60,
        bool $requiresOtp = false,
        mixed $risk = null,
        array $errors = []
    ): JsonResponse {

        return response()->json([
            'status' => false,
            'success' => false,
            'status_code' => Response::HTTP_TOO_MANY_REQUESTS,
            'message' => $message,
            'errors' => $errors,
            'requires_otp' => $requiresOtp,
            'risk' => $risk,
            'retry_after' => $retryAfter,
            'data' => [],
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function paginationResponse($paginator, array $extraData = []): array
    {
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $links = [];

        // Previous
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => 'Previous',
            'active' => false,
        ];

        // Page Numbers
        for ($page = 1; $page <= $lastPage; $page++) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string) $page,
                'active' => $page === $currentPage,
            ];
        }

        // Next
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next',
            'active' => false,
        ];

        $pagination = [
            'current_page' => $currentPage,
            'first_page_url' => $paginator->url(1),
            'from' => $paginator->firstItem(),
            'last_page' => $lastPage,
            'last_page_url' => $paginator->url($lastPage),
            'links' => $links,
            'next_page_url' => $paginator->nextPageUrl(),
            'path' => $paginator->path(),
            'per_page' => (int) $paginator->perPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'to' => $paginator->lastItem(),
            'total' => (int) $paginator->total(),
        ];

        return array_merge($pagination, $extraData);
    }
}