<?php

namespace App\Support\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Return a standardized successful JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array  $meta
     * @return JsonResponse
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = Response::HTTP_OK, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'status' => $statusCode,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a standardized created resource JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @return JsonResponse
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a standardized error JSON response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  mixed  $errors
     * @param  string|null  $errorCode
     * @return JsonResponse
     */
    public static function error(string $message = 'An error occurred', int $statusCode = Response::HTTP_BAD_REQUEST, mixed $errors = null, ?string $errorCode = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'status' => $statusCode,
            'message' => $message,
            'errors' => $errors,
        ];

        if ($errorCode) {
            $payload['error_code'] = $errorCode;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a standardized unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED, null, 'AUTH_REQUIRED');
    }

    /**
     * Return a standardized forbidden response.
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN, null, 'PERMISSION_DENIED');
    }

    /**
     * Return a standardized not found response.
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND, null, 'NOT_FOUND');
    }

    /**
     * Return a standardized validation error response.
     */
    public static function validationError(mixed $errors, string $message = 'The given data was invalid'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors, 'VALIDATION_FAILED');
    }
}
