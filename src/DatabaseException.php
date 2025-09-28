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
        int $code = 0,
        ?Throwable $previous = null,
        ?string $query = null,
        ?array $parameters = null
    ) {
        parent::__construct($message, $code, $previous);
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
}