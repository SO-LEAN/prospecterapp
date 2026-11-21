<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Who an event invites: the booker of an exchange booked by the public link of its holder, at their e-mail, the
 * provider sending them the invitation and its updates from the calendar of the holder; for a call, the phone the
 * holder calls them at, and the first name of the holder; whether the push tells them, the event new to them or moved.
 */
final readonly class Guest
{
    public function __construct(
        public Email $email,
        public ?PhoneNumber $calledAt = null,
        public ?string $calledBy = null,
        public bool $isTold = true,
    ) {
    }
}
