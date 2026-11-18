<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The host never set their booking page. A link no page has offers nothing, without saying why (ADR 0153).
 */
final class UnknownBookingPage extends NotFound
{
    public static function of(MemberId $host): self
    {
        return new self(sprintf('No booking page of %s', $host));
    }
}
