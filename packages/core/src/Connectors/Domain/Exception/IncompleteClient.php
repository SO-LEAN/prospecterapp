<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A client of Prospecter at a provider is its id and its secret, both given by the provider.
 */
final class IncompleteClient extends BusinessRuleViolation
{
    public static function withoutItsId(): self
    {
        return self::because('A client needs its id', 'clientId');
    }

    public static function withoutItsSecret(): self
    {
        return self::because('A client needs its secret', 'clientSecret');
    }
}
