<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * The person that an event invites: whoever booked the exchange through its holder's public link. The guest is
 * invited at their e-mail. The provider sends them the invitation and its updates from the holder's calendar. For a
 * call, the guest also holds the phone number that the holder calls them at, and the holder's first name. It says
 * whether the push tells them, because the event is new to them or has moved.
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
