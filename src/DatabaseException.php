<?php

namespace BMorais\Database;

use Exception;
use Throwable;

/**
 * Custom Database Exception
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
 * @package bmorais\database
 */
class DatabaseException extends Exception
{
    private ?string $query;
    private ?array $parameters;

    public function __construct(
        string $message,
        int|string $code = 0,  // ✅ Aceita int ou string
        ?Throwable $previous = null,
        ?string $query = null,
        ?array $parameters = null
    ) {
        // ✅ Converte string para int de forma segura
        $intCode = is_string($code) ? (int)$code : $code;
        if ($intCode === 0 && is_string($code) && !empty($code)) {
            // Se a conversão resultou em 0 mas o código original não era vazio,
            // usa um código padrão
            $intCode = 1000;
        }

        parent::__construct($message, $intCode, $previous);
        $this->query = $query;
        $this->parameters = $parameters;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function getContextInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'query' => $this->query,
            'parameters' => $this->parameters,
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }

    /**
     * Get original PDO error code (may be string)
     */
    public function getOriginalCode(): int|string
    {
        $previous = $this->getPrevious();
        return $previous ? $previous->getCode() : $this->getCode();
    }
}