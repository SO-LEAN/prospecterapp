<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * No booking page has this code, a former link or one nobody drew; or the host never set theirs.
 */
final class UnknownBookingPage extends NotFound
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('No booking page at %s', $code));
    }

    public static function of(MemberId $host): self
    {
        return new self(sprintf('No booking page of %s', $host));
    }
}
