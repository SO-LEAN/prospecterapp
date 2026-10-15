<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A business rule refuses the change, naming the faulty field when there is one.
 */
class BusinessRuleViolation extends BusinessException
{
    public const string WHOLE = '*';

    final protected function __construct(string $message, public readonly string $field, ?\Throwable $previous)
    {
        parent::__construct($message, previous: $previous);
    }

    public static function because(string $message, string $field = self::WHOLE, ?\Throwable $previous = null): static
    {
        return new static($message, $field, $previous);
    }
}
