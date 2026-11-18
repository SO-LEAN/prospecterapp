<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Accounts knows no member of this identity: nobody sets a booking page for them.
 */
final class UnknownMember extends NotFound
{
    public static function identifiedBy(MemberId $id): self
    {
        return new self(sprintf('Member %s not found', $id));
    }
}
