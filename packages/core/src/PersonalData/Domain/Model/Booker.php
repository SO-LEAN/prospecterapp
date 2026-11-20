<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * A person who booked an appointment with a host, as they said who they are.
 */
final readonly class Booker
{
    public function __construct(
        public PersonName $name,
        public PhoneNumber $phoneNumber,
        public Email $email,
    ) {
    }

    public function contactDetails(): ContactDetails
    {
        return new ContactDetails($this->email, $this->phoneNumber);
    }
}
