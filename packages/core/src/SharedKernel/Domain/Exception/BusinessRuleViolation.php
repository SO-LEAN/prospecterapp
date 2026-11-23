<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A business rule refuses the change. The refusal names the faulty field when there is one. It also gives what it
 * is about by name (the refused value, for example), so that a client can word it in its own language. A rule that
 * deserves its own wording gets its own class, which names the refusal (ADR 0073).
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
