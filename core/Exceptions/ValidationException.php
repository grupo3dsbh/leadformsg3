<?php

declare(strict_types=1);

namespace Core\Exceptions;

class ValidationException extends \RuntimeException
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    public function __construct(string $message = 'Validation failed', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error message across all fields.
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }
}
