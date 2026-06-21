<?php

namespace Recurrente\Laravel\Exceptions;

use Exception;
use Throwable;

class RecurrenteException extends Exception
{
    /** @var array<string,mixed> */
    public array $response;

    /**
     * @param array<string,mixed> $response
     */
    public function __construct(string $message, int $code = 0, array $response = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @param array<string,mixed> $response
     */
    public static function fromResponse(int $status, array $response): self
    {
        $message = $response['message']
            ?? $response['error']
            ?? ($response['errors'][0]['message'] ?? null)
            ?? "Recurrente API respondió con estado {$status}.";

        return new self($message, $status, $response);
    }

    public static function missingCredentials(): self
    {
        return new self('Falta configurar la llave secreta (X-SECRET-KEY) de Recurrente.', 0);
    }
}
