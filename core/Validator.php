<?php

declare(strict_types=1);

namespace Core;

/**
 * Input Validator
 *
 * Validates an associative array of data against a set of rules.
 *
 * Supported rules:
 *   required, nullable, email, url, min:{n}, max:{n}, minlen:{n},
 *   maxlen:{n}, between:{min},{max}, numeric, integer, alpha,
 *   alpha_num, alpha_dash, string, boolean, array, date,
 *   date_format:{format}, in:{a},{b},{c}, not_in:{a},{b},{c},
 *   regex:{pattern}, confirmed, same:{field}, different:{field},
 *   unique:{table},{column},{ignoreId?}, exists:{table},{column},
 *   cpf, cnpj, phone, file, image, mimes:{ext1},{ext2},
 *   max_file:{kb}
 *
 * Usage:
 *
 *   $v = new Validator($data, [
 *       'name'  => 'required|minlen:3|maxlen:100',
 *       'email' => 'required|email|unique:users,email',
 *       'cpf'   => 'required|cpf',
 *       'phone' => 'required|phone',
 *   ]);
 *
 *   if ($v->fails()) {
 *       $errors = $v->errors();
 *   }
 */
class Validator
{
    private array $data;

    /** @var array<string, list<string>> Parsed rules per field. */
    private array $rules;

    /** @var array<string, list<string>> Collected error messages per field. */
    private array $errors = [];

    /** @var array<string, string> Custom error messages (optional). */
    private array $customMessages;

    /** Whether validation has been executed. */
    private bool $validated = false;

    // ------------------------------------------------------------------
    // Constructor
    // ------------------------------------------------------------------

    /**
     * @param array $data             Input data to validate.
     * @param array $rules            Validation rules (field => 'rule|rule:param').
     * @param array $customMessages   Optional per-rule custom messages.
     */
    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data           = $data;
        $this->customMessages = $customMessages;
        $this->rules          = $this->parseRules($rules);
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Run validation and return true if all rules pass.
     */
    public function passes(): bool
    {
        $this->runValidation();

        return empty($this->errors);
    }

    /**
     * Inverse of passes().
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Return all error messages grouped by field.
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        $this->runValidation();

        return $this->errors;
    }

    /**
     * Return the first error message for a specific field.
     */
    public function firstError(string $field): ?string
    {
        $errors = $this->errors();

        return $errors[$field][0] ?? null;
    }

    /**
     * Return only the validated (present and passing) data keys.
     */
    public function validated(): array
    {
        $this->runValidation();

        $result = [];

        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Parse rules
    // ------------------------------------------------------------------

    /**
     * Normalise rules from strings to arrays.
     *
     * @return array<string, list<string>>
     */
    private function parseRules(array $rules): array
    {
        $parsed = [];

        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $parsed[$field] = array_map('trim', $fieldRules);
        }

        return $parsed;
    }

    // ------------------------------------------------------------------
    // Run validation
    // ------------------------------------------------------------------

    private function runValidation(): void
    {
        if ($this->validated) {
            return;
        }

        $this->validated = true;

        foreach ($this->rules as $field => $rules) {
            $value      = $this->data[$field] ?? null;
            $isNullable = in_array('nullable', $rules, true);
            $isRequired = in_array('required', $rules, true);

            // If nullable and value is empty, skip remaining rules.
            if ($isNullable && ($value === null || $value === '')) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                $ruleName = $rule;
                $params   = [];

                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));

                if (!method_exists($this, $method)) {
                    throw new \RuntimeException("Unknown validation rule: {$ruleName}");
                }

                $passed = $this->{$method}($field, $value, $params);

                if (!$passed) {
                    $this->addError($field, $ruleName, $params);

                    // Stop on first failure for this field (fail-fast).
                    break;
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Error messages
    // ------------------------------------------------------------------

    private function addError(string $field, string $rule, array $params = []): void
    {
        $customKey = "{$field}.{$rule}";

        if (isset($this->customMessages[$customKey])) {
            $message = $this->customMessages[$customKey];
        } else {
            $message = $this->defaultMessage($field, $rule, $params);
        }

        $this->errors[$field][] = $message;
    }

    private function defaultMessage(string $field, string $rule, array $params): string
    {
        $label = str_replace('_', ' ', $field);

        return match ($rule) {
            'required'    => "The {$label} field is required.",
            'email'       => "The {$label} must be a valid email address.",
            'url'         => "The {$label} must be a valid URL.",
            'min'         => "The {$label} must be at least {$params[0]}.",
            'max'         => "The {$label} must not be greater than {$params[0]}.",
            'minlen'      => "The {$label} must be at least {$params[0]} characters.",
            'maxlen'      => "The {$label} must not exceed {$params[0]} characters.",
            'between'     => "The {$label} must be between {$params[0]} and {$params[1]}.",
            'numeric'     => "The {$label} must be a number.",
            'integer'     => "The {$label} must be an integer.",
            'alpha'       => "The {$label} may only contain letters.",
            'alpha_num'   => "The {$label} may only contain letters and numbers.",
            'alpha_dash'  => "The {$label} may only contain letters, numbers, dashes and underscores.",
            'string'      => "The {$label} must be a string.",
            'boolean'     => "The {$label} must be true or false.",
            'array'       => "The {$label} must be an array.",
            'date'        => "The {$label} must be a valid date.",
            'date_format' => "The {$label} does not match the format {$params[0]}.",
            'in'          => "The selected {$label} is invalid.",
            'not_in'      => "The selected {$label} is invalid.",
            'regex'       => "The {$label} format is invalid.",
            'confirmed'   => "The {$label} confirmation does not match.",
            'same'        => "The {$label} and {$params[0]} must match.",
            'different'   => "The {$label} and {$params[0]} must be different.",
            'unique'      => "The {$label} has already been taken.",
            'exists'      => "The selected {$label} does not exist.",
            'cpf'         => "The {$label} must be a valid CPF.",
            'cnpj'        => "The {$label} must be a valid CNPJ.",
            'phone'       => "The {$label} must be a valid phone number.",
            'file'        => "The {$label} must be a file.",
            'image'       => "The {$label} must be an image.",
            'mimes'       => "The {$label} must be a file of type: " . implode(', ', $params) . '.',
            'max_file'    => "The {$label} must not be larger than {$params[0]} KB.",
            default       => "The {$label} is invalid.",
        };
    }

    // ==================================================================
    // Validation rules
    // ==================================================================

    private function validateRequired(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    private function validateEmail(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true; // let 'required' handle empty.
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateUrl(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateMin(string $field, mixed $value, array $params): bool
    {
        $min = (float) ($params[0] ?? 0);

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        return strlen((string) $value) >= (int) $min;
    }

    private function validateMax(string $field, mixed $value, array $params): bool
    {
        $max = (float) ($params[0] ?? PHP_INT_MAX);

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        return strlen((string) $value) <= (int) $max;
    }

    private function validateMinlen(string $field, mixed $value, array $params): bool
    {
        return mb_strlen((string) $value) >= (int) ($params[0] ?? 0);
    }

    private function validateMaxlen(string $field, mixed $value, array $params): bool
    {
        return mb_strlen((string) $value) <= (int) ($params[0] ?? PHP_INT_MAX);
    }

    private function validateBetween(string $field, mixed $value, array $params): bool
    {
        $min = (float) ($params[0] ?? 0);
        $max = (float) ($params[1] ?? PHP_INT_MAX);
        $val = is_numeric($value) ? (float) $value : mb_strlen((string) $value);

        return $val >= $min && $val <= $max;
    }

    private function validateNumeric(string $field, mixed $value, array $params): bool
    {
        return is_numeric($value);
    }

    private function validateInteger(string $field, mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateAlpha(string $field, mixed $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM]+$/u', (string) $value);
    }

    private function validateAlphaNum(string $field, mixed $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM\pN]+$/u', (string) $value);
    }

    private function validateAlphaDash(string $field, mixed $value, array $params): bool
    {
        return (bool) preg_match('/^[\pL\pM\pN_-]+$/u', (string) $value);
    }

    private function validateString(string $field, mixed $value, array $params): bool
    {
        return is_string($value);
    }

    private function validateBoolean(string $field, mixed $value, array $params): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    private function validateArray(string $field, mixed $value, array $params): bool
    {
        return is_array($value);
    }

    private function validateDate(string $field, mixed $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return strtotime($value) !== false;
    }

    private function validateDateFormat(string $field, mixed $value, array $params): bool
    {
        $format = $params[0] ?? 'Y-m-d';
        $d      = \DateTimeImmutable::createFromFormat($format, (string) $value);

        return $d !== false && $d->format($format) === (string) $value;
    }

    private function validateIn(string $field, mixed $value, array $params): bool
    {
        return in_array((string) $value, $params, true);
    }

    private function validateNotIn(string $field, mixed $value, array $params): bool
    {
        return !in_array((string) $value, $params, true);
    }

    private function validateRegex(string $field, mixed $value, array $params): bool
    {
        $pattern = $params[0] ?? '';

        return (bool) preg_match($pattern, (string) $value);
    }

    private function validateConfirmed(string $field, mixed $value, array $params): bool
    {
        $confirmationField = $field . '_confirmation';

        return isset($this->data[$confirmationField])
            && $value === $this->data[$confirmationField];
    }

    private function validateSame(string $field, mixed $value, array $params): bool
    {
        $otherField = $params[0] ?? '';

        return isset($this->data[$otherField]) && $value === $this->data[$otherField];
    }

    private function validateDifferent(string $field, mixed $value, array $params): bool
    {
        $otherField = $params[0] ?? '';

        return !isset($this->data[$otherField]) || $value !== $this->data[$otherField];
    }

    // ------------------------------------------------------------------
    // Database rules
    // ------------------------------------------------------------------

    /**
     * unique:table,column,ignoreId
     * Ensures the value does not exist in the specified table/column.
     */
    private function validateUnique(string $field, mixed $value, array $params): bool
    {
        $table    = $params[0] ?? '';
        $column   = $params[1] ?? $field;
        $ignoreId = $params[2] ?? null;

        if ($table === '') {
            return true;
        }

        $db    = Database::getInstance();
        $query = $db->table($table)->where($column, $value);

        if ($ignoreId !== null) {
            $query->whereRaw('id != :ignore_id', [':ignore_id' => $ignoreId]);
        }

        return !$query->exists();
    }

    /**
     * exists:table,column
     * Ensures the value exists in the specified table/column.
     */
    private function validateExists(string $field, mixed $value, array $params): bool
    {
        $table  = $params[0] ?? '';
        $column = $params[1] ?? $field;

        if ($table === '') {
            return true;
        }

        return Database::getInstance()
            ->table($table)
            ->where($column, $value)
            ->exists();
    }

    // ------------------------------------------------------------------
    // Brazilian document rules
    // ------------------------------------------------------------------

    /**
     * Validate a Brazilian CPF (Cadastro de Pessoas Fisicas).
     */
    private function validateCpf(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        // Strip non-digits.
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // Reject known invalid patterns (all same digit).
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate check digits.
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($c = 0; $c < $t; $c++) {
                $sum += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a Brazilian CNPJ (Cadastro Nacional da Pessoa Juridica).
     */
    private function validateCnpj(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $cnpj = preg_replace('/\D/', '', (string) $value);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // First check digit.
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum      = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1    = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        // Second check digit.
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum      = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2    = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cnpj[13] === $digit2;
    }

    /**
     * Validate a Brazilian phone number.
     * Accepts formats: (XX) XXXXX-XXXX, (XX) XXXX-XXXX, or just digits
     * (10 or 11 digits).
     */
    private function validatePhone(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        // Brazilian landlines: 10 digits, mobiles: 11 digits.
        // Also accept international format with country code (12-13 digits).
        return in_array(strlen($digits), [10, 11, 12, 13], true);
    }

    // ------------------------------------------------------------------
    // File rules
    // ------------------------------------------------------------------

    private function validateFile(string $field, mixed $value, array $params): bool
    {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
    }

    private function validateImage(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $mime         = mime_content_type($_FILES[$field]['tmp_name']);

        return in_array($mime, $allowedMimes, true);
    }

    private function validateMimes(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));

        return in_array($extension, $params, true);
    }

    private function validateMaxFile(string $field, mixed $value, array $params): bool
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $maxKb = (int) ($params[0] ?? 0);

        return $_FILES[$field]['size'] <= ($maxKb * 1024);
    }
}
