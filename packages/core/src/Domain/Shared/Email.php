<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Shared;

final readonly class Email implements \Stringable
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleViolation(sprintf('Email "%s" is not valid', $value), 'email');
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
