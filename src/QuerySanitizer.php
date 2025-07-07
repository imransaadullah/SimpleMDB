<?php

namespace SimpleMDB;

class QuerySanitizer
{
    private array $typeValidators = [];
    private array $customValidators = [];
    private array $sanitizers = [];

    public function __construct()
    {
        $this->registerDefaultValidators();
        $this->registerDefaultSanitizers();
    }

    private function registerDefaultValidators(): void
    {
        // Basic type validators
        $this->typeValidators = [
            'int' => fn($value) => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'float' => fn($value) => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            'string' => fn($value) => is_string($value),
            'email' => fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => fn($value) => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'boolean' => fn($value) => is_bool($value) || in_array(strtolower((string)$value), ['true', 'false', '1', '0']),
            'array' => fn($value) => is_array($value),
            'date' => fn($value) => strtotime($value) !== false,
            'json' => fn($value) => json_decode($value) !== null && json_last_error() === JSON_ERROR_NONE,
            'ip' => fn($value) => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'mac' => fn($value) => filter_var($value, FILTER_VALIDATE_MAC) !== false
        ];
    }

    private function registerDefaultSanitizers(): void
    {
        // Basic sanitizers
        $this->sanitizers = [
            'int' => fn($value) => filter_var($value, FILTER_SANITIZE_NUMBER_INT),
            'float' => fn($value) => filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'string' => fn($value) => filter_var($value, FILTER_SANITIZE_STRING),
            'email' => fn($value) => filter_var($value, FILTER_SANITIZE_EMAIL),
            'url' => fn($value) => filter_var($value, FILTER_SANITIZE_URL),
            'sql' => function($value) {
                if (is_array($value)) {
                    return array_map([$this, 'escapeSql'], $value);
                }
                return $this->escapeSql($value);
            },
            'html' => fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            'trim' => fn($value) => trim($value),
            'lowercase' => fn($value) => strtolower($value),
            'uppercase' => fn($value) => strtoupper($value)
        ];
    }

    public function addValidator(string $name, callable $validator): self
    {
        $this->customValidators[$name] = $validator;
        return $this;
    }

    public function addSanitizer(string $name, callable $sanitizer): self
    {
        $this->sanitizers[$name] = $sanitizer;
        return $this;
    }

    public function validate($value, string $type, array $options = []): bool
    {
        // Check custom validators first
        if (isset($this->customValidators[$type])) {
            return $this->customValidators[$type]($value, $options);
        }

        // Then check type validators
        if (isset($this->typeValidators[$type])) {
            return $this->typeValidators[$type]($value);
        }

        // Special validation cases
        switch ($type) {
            case 'required':
                return $value !== null && $value !== '';
            
            case 'min':
                return is_numeric($value) && $value >= ($options['value'] ?? 0);
            
            case 'max':
                return is_numeric($value) && $value <= ($options['value'] ?? PHP_FLOAT_MAX);
            
            case 'between':
                return is_numeric($value) && 
                       $value >= ($options['min'] ?? 0) && 
                       $value <= ($options['max'] ?? PHP_FLOAT_MAX);
            
            case 'length':
                return strlen($value) === ($options['value'] ?? 0);
            
            case 'minLength':
                return strlen($value) >= ($options['value'] ?? 0);
            
            case 'maxLength':
                return strlen($value) <= ($options['value'] ?? PHP_FLOAT_MAX);
            
            case 'pattern':
                return isset($options['pattern']) && preg_match($options['pattern'], $value);
            
            case 'in':
                return isset($options['values']) && in_array($value, $options['values']);
            
            case 'notIn':
                return isset($options['values']) && !in_array($value, $options['values']);
            
            default:
                throw new \InvalidArgumentException("Unknown validation type: {$type}");
        }
    }

    public function sanitize($value, string|array $types): mixed
    {
        if (is_array($types)) {
            foreach ($types as $type) {
                $value = $this->applySanitizer($value, $type);
            }
            return $value;
        }

        return $this->applySanitizer($value, $types);
    }

    private function applySanitizer($value, string $type): mixed
    {
        if (!isset($this->sanitizers[$type])) {
            throw new \InvalidArgumentException("Unknown sanitizer type: {$type}");
        }

        return $this->sanitizers[$type]($value);
    }

    private function escapeSql($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        // Remove any invalid UTF-8 characters
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        
        // Escape special characters
        return addslashes($value);
    }

    public function validateArray(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : [$fieldRules];
            
            foreach ($fieldRules as $rule) {
                $options = [];
                
                // Parse rule with options
                if (is_string($rule) && strpos($rule, ':') !== false) {
                    list($rule, $optionString) = explode(':', $rule, 2);
                    $options = $this->parseOptions($optionString);
                }

                // Skip validation if field is not required and empty
                if ($rule !== 'required' && !isset($data[$field])) {
                    continue;
                }

                if (!$this->validate($data[$field] ?? null, $rule, $options)) {
                    $errors[$field][] = $this->getErrorMessage($field, $rule, $options);
                }
            }
        }

        return $errors;
    }

    private function parseOptions(string $optionString): array
    {
        $options = [];
        $pairs = explode(',', $optionString);
        
        foreach ($pairs as $pair) {
            if (strpos($pair, '=') !== false) {
                list($key, $value) = explode('=', $pair, 2);
                $options[$key] = $value;
            } else {
                $options['value'] = $pair;
            }
        }

        return $options;
    }

    private function getErrorMessage(string $field, string $rule, array $options = []): string
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'int' => "The {$field} must be an integer.",
            'float' => "The {$field} must be a floating point number.",
            'string' => "The {$field} must be a string.",
            'email' => "The {$field} must be a valid email address.",
            'url' => "The {$field} must be a valid URL.",
            'boolean' => "The {$field} must be a boolean value.",
            'array' => "The {$field} must be an array.",
            'date' => "The {$field} must be a valid date.",
            'json' => "The {$field} must be a valid JSON string.",
            'ip' => "The {$field} must be a valid IP address.",
            'mac' => "The {$field} must be a valid MAC address.",
            'min' => "The {$field} must be at least {$options['value']}.",
            'max' => "The {$field} must not be greater than {$options['value']}.",
            'between' => "The {$field} must be between {$options['min']} and {$options['max']}.",
            'length' => "The {$field} must be exactly {$options['value']} characters.",
            'minLength' => "The {$field} must be at least {$options['value']} characters.",
            'maxLength' => "The {$field} must not exceed {$options['value']} characters.",
            'pattern' => "The {$field} format is invalid.",
            'in' => "The selected {$field} is invalid.",
            'notIn' => "The selected {$field} is invalid."
        ];

        return $messages[$rule] ?? "The {$field} field is invalid.";
    }
} 