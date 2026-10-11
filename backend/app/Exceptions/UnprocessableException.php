<?php

namespace App\Exceptions;

use DomainException;

abstract class UnprocessableException extends DomainException
{
    public function __construct(string $message, public readonly string $field)
    {
        parent::__construct($message);
    }
}
