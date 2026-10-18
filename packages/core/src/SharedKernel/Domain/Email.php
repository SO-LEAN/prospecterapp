<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Exception\InvalidEmail;

/**
 * In lower case: an address is one whatever the case it was typed in.
 */
final readonly class Email implements \Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::of($value);
        }
        $this->value = mb_strtolower($value);
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
