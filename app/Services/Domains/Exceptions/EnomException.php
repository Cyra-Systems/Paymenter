<?php

namespace App\Services\Domains\Exceptions;

use Exception;
use Throwable;

class EnomException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, public array $payload = [])
    {
        parent::__construct($message, $code, $previous);
    }
}
