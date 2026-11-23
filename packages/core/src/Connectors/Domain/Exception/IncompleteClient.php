<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A Prospecter client at a provider needs its id and its secret. The provider gives both.
 */
final class IncompleteClient extends BusinessRuleViolation
{
    public static function withoutItsId(): self
    {
        return self::because('The client id is required', 'clientId');
    }

    public static function withoutItsSecret(): self
    {
        return self::because('The client secret is required', 'clientSecret');
    }
}
