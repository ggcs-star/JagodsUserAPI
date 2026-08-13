<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Business-Key');

        if (!$apiKey) {
            return response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 401,
                'errors' => [],
                'message' => 'Business API key is required.',
                'data' => [],
            ], 401);
        }

        if (!hash_equals(
            (string) config('services.business_api.key'),
            (string) $apiKey
        )) {
            return response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 403,
                'errors' => [],
                'message' => 'Invalid Business API key.',
                'data' => [],
            ], 403);
        }

        return $next($request);
    }
}