<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The host never set up their booking page. A link that leads to no page offers nothing, and the refusal does not say
 * why (ADR 0153).
 */
final class UnknownBookingPage extends NotFound
{
    public static function of(MemberId $host): self
    {
        return new self(sprintf('Host %s has no booking page', $host));
    }
}
