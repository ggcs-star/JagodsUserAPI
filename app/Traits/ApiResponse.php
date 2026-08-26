<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{

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

        if (!empty($pagination)) {
            $pagination['data'] = $data;
            $data = $pagination;
        }

        $response = [
            'status' => true,
            'success' => true,
            'status_code' => $statusCode,
            'errors' => [],
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }


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

     public function errorResponsecart(
        string $message = 'Something went wrong',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        mixed $data = []
    ): JsonResponse {
        if ($data === null) {
            $data = [];
        }

        return response()->json([
            'status' => true,
            'success' => true,
            'status_code' => $statusCode,
            'errors' => $errors,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function loginSuccessResponse(
        string $message = 'Login successful',
        mixed $userResource = [],
        array $additionalData = [],
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'success' => true,
            'status_code' => $statusCode,
            'errors' => [],
            'message' => $message,
            'data' => $userResource,
            'meta' => $additionalData
        ], $statusCode);
    }


    public function loginFailedResponse(
        string $message = 'Authentication failed',
        int $statusCode = Response::HTTP_UNAUTHORIZED,
        bool $requiresOtp = false,
        mixed $risk = null,
        mixed $deviceId = null,
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'status' => false,
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
            'errors' => $errors,
            'requires_otp' => $requiresOtp,
            'risk' => $risk,
            'device_id' => $deviceId,
            'data' => [],
        ], $statusCode);
    }


    public function rateLimitResponse(
        string $message = 'Too many attempts. Please try again later.',
        int $retryAfterSeconds = 60
    ): JsonResponse {
        return response()->json([
            'status' => false,
            'success' => false,
            'status_code' => Response::HTTP_TOO_MANY_REQUESTS,
            'message' => $message,
            'errors' => [],
            'retry_after' => $retryAfterSeconds,
            'data' => [],
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }


    public function validationResponse(array $errors): JsonResponse
    {
        $messages = collect($errors)
            ->flatten()
            ->filter()
            ->values()
            ->implode(' ');

        return $this->errorResponse(
            message: $messages ?: 'Validation Error',
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors
        );
    }

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


    public function updatedResponse(
        string $message = 'Updated Successfully',
        mixed $data = []
    ): JsonResponse {
        return $this->successResponse(
            message: $message,
            data: $data
        );
    }


    public function deletedResponse(
        string $message = 'Deleted Successfully'
    ): JsonResponse {
        return $this->successResponse(
            message: $message,
            data: []
        );
    }




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



    protected function paginationResponse($paginator, mixed $data = [], array $extraData = []): array
    {
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $links = [];

        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        for ($page = 1; $page <= $lastPage; $page++) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string) $page,
                'active' => $page == $currentPage,
            ];
        }

        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return array_merge([
            'current_page' => $currentPage,

            'data' => $data,

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
        ], $extraData);
    }
    public function successPaginationResponse(
        string $message,
        $paginator,
        mixed $data,
        int $statusCode = Response::HTTP_OK,
        ?array $meta = null
    ): JsonResponse {

        $response = [
            'status' => true,
            'success' => true,
            'status_code' => $statusCode,
            'errors' => [],
            'message' => $message,

            'data' => $this->paginationResponse($paginator, $data),
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    public function otpRequiredResponse(
        string $message = 'OTP verification required.',
        mixed $risk = null,
        mixed $deviceId = null,
        array $data = []
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'success' => true,
            'status_code' => 491,
            'message' => $message,
            'errors' => [],
            'requires_otp' => true,
            'risk' => $risk,
            'device_id' => $deviceId,
            'data' => $data,
        ], 491);
    }

     public function otpRequiredResponseProfile(
        string $message = 'OTP verification required.',
        mixed $risk = null,
        mixed $deviceId = null,
        array $data = []
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'success' => true,
            'status_code' => 493,
            'message' => $message,
            'errors' => [],
            'requires_otp' => true,
            'risk' => $risk,
            'device_id' => $deviceId,
            'data' => $data,
        ], 493);
    }
}