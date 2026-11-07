<?php

namespace App\Support\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class AgeVerificationException extends DomainException
{
    protected string $errorCode = 'AGE_REQUIREMENT_FAILED';
    protected int $statusCode = Response::HTTP_FORBIDDEN;

    public function __construct(string $message = 'You must be at least 18 years old to access LocalDateHub.')
    {
        parent::__construct($message);
    }
}
