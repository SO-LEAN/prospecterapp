<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * The answer of a provider awaited by no authorization of the member: unknown, expired, or asked by another member.
 */
final class UnknownAuthorization extends NotFound
{
    public static function forTheAnswer(): self
    {
        return new self('No authorization is waiting for this answer');
    }
}
