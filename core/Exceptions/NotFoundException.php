<?php

declare(strict_types=1);

namespace Core\Exceptions;

class NotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Not Found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
