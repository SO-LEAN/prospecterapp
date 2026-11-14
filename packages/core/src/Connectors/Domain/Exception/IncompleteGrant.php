<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The member unticked, at the consent, the access to the calendar a connection is for.
 */
final class IncompleteGrant extends BusinessRuleViolation
{
    public static function of(Provider $provider): self
    {
        return self::because('The access to the calendar was not granted', context: ['provider' => $provider->value]);
    }
}
