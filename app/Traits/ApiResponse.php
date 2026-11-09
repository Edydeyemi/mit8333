<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Standard Success Response
     */
    protected function success($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Success response alias matching AGENTS.md specification.
     */
    protected function successResponse($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return $this->success($data, $message, $code);
    }

    /**
     * Standard Error Response
     */
    protected function error(string $message, int $code = 400, ?string $errorCode = null, ?array $errors = null): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'error_code' => $errorCode,
            'message' => $message,
            'data' => null,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }

    /**
     * Error response alias matching AGENTS.md specification.
     */
    protected function errorResponse(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        return $this->error($message, $code, null, $errors);
    }
}
