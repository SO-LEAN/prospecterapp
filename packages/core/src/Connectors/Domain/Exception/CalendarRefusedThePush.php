<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The provider refused a push for good: trying it again would be refused again (ADR 0127).
 */
final class CalendarRefusedThePush extends BusinessRuleViolation
{
    public static function by(Provider $provider, int $status): self
    {
        return self::because(sprintf('%s refused the event of the exchange', ucfirst($provider->value)), context: ['status' => (string) $status]);
    }
}
