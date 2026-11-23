<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;

/**
 * A person who booked an appointment with a host, as they described themselves.
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
