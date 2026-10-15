<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Exception;

/**
 * A request the application refuses.
 */
class UseCaseException extends \RuntimeException
{
    /**
     * @param array<string, string> $requestErrors message by request field, "*" for the request as a whole
     */
    public function __construct(string $message = '', private readonly array $requestErrors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function getRequestErrors(): array
    {
        return $this->requestErrors;
    }
}
