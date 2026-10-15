<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A business rule refuses the change, naming the faulty field when there is one.
 */
class BusinessRuleViolation extends BusinessException
{
    public const string WHOLE = '*';

    final protected function __construct(string $message, public readonly string $field)
    {
        parent::__construct($message);
    }

    public static function because(string $message, string $field = self::WHOLE): static
    {
        return new static($message, $field);
    }
}
