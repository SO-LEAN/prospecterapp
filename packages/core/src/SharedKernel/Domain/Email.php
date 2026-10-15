<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

final readonly class Email implements \Stringable
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw BusinessRuleViolation::because(sprintf('Email "%s" is not valid', $value), 'email');
        }
    }

    public static function orNone(?string $value): ?self
    {
        return null === $value || '' === $value ? null : new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
