<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * The lasting access that a provider gave to a member's account. Prospecter uses it while the member is away
 * (OAuth 2, ADR 0120).
 */
final readonly class RefreshToken implements \Stringable
{
    public function __construct(public string $value)
    {
        if ('' === $value) {
            throw new \InvalidArgumentException('A refresh token is never empty');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
