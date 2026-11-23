<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The provider refused a push for good. The provider would refuse it again if it were tried again (ADR 0127).
 */
final class CalendarRefusedThePush extends BusinessRuleViolation
{
    public static function by(Provider $provider, int $status): self
    {
        return self::because(sprintf('%s refused the exchange\'s event', ucfirst($provider->value)), context: ['status' => (string) $status]);
    }
}
