<?php

namespace App\Support\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class DomainException extends Exception
{
    protected string $errorCode = 'DOMAIN_ERROR';
    protected int $statusCode = Response::HTTP_BAD_REQUEST;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            statusCode: $this->statusCode,
            errors: null,
            errorCode: $this->errorCode
        );
    }
}
