<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A business rule refuses the change, naming the faulty field when there is one, and what it is about (the
 * value refused...) by name, for a client to word it in its own language. A rule worth its own wording gets
 * its own class, which names the refusal (ADR 0073).
 */
class BusinessRuleViolation extends BusinessException
{
    public const string WHOLE = '*';

    /**
     * @param array<string, string> $context
     */
    final protected function __construct(string $message, public readonly string $field, ?\Throwable $previous, public readonly array $context)
    {
        parent::__construct($message, previous: $previous);
    }

    /**
     * @param array<string, string> $context what the refusal is about, by name
     */
    public static function because(string $message, string $field = self::WHOLE, ?\Throwable $previous = null, array $context = []): static
    {
        return new static($message, $field, $previous, $context);
    }
}
