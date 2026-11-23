<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * No authorization of the member awaits the provider's answer. The authorization is unknown, expired, or asked by
 * another member.
 */
final class UnknownAuthorization extends NotFound
{
    public static function forTheAnswer(): self
    {
        return new self('No authorization is waiting for this answer');
    }
}
