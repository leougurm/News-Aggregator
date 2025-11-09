<?php

namespace App\Exceptions;

use Exception;

class RateLimitException extends \RuntimeException
{
    public function __construct(string $message = "Rate limit exceeded", int $code = 429)
    {
        parent::__construct($message, $code);
    }
}
