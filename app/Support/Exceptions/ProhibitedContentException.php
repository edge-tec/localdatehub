<?php

namespace App\Support\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ProhibitedContentException extends DomainException
{
    protected string $errorCode = 'PROHIBITED_CONTENT_DETECTED';
    protected int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(string $message = 'The submission contains prohibited commercial solicitation or non-compliant content under LocalDateHub safety rules.')
    {
        parent::__construct($message);
    }
}
