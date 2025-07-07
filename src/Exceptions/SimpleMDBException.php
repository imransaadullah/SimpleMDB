<?php

namespace SimpleMDB\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception class for SimpleMDB
 */
class SimpleMDBException extends Exception
{
    protected array $context = [];
    protected ?string $sql = null;
    protected array $params = [];
    protected ?string $errorCode = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function withSql(string $sql, array $params = []): self
    {
        $this->sql = $sql;
        $this->params = $params;
        return $this;
    }

    public function withErrorCode(string $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    public function getSql(): ?string
    {
        return $this->sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->sql) {
            $message .= "\nSQL: " . $this->sql;
        }
        
        if (!empty($this->params)) {
            $message .= "\nParams: " . json_encode($this->params);
        }
        
        if ($this->errorCode) {
            $message .= "\nError Code: " . $this->errorCode;
        }
        
        if (!empty($this->context)) {
            $message .= "\nContext: " . json_encode($this->context);
        }
        
        return $message;
    }

    /**
     * Convert exception to array for logging
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'sql' => $this->sql,
            'params' => $this->params,
            'error_code' => $this->errorCode,
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
} 