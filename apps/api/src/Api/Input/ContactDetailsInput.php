<?php

namespace App\Api\Input;

use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactDetailsInput
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
    ) {
    }

    public function toContactDetails(): ContactDetails
    {
        return new ContactDetails(Email::orNone($this->email), $this->phoneNumber, $this->language);
    }
}
