<?php

namespace App\PersonalData\Input;

use App\Shared\Input\ContactDetailsInput;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What is known of a person when the account starts prospecting them (the shape of ContactResource).
 */
final readonly class CollectContactInput
{
    public function __construct(
        #[Assert\Valid]
        public PersonNameInput $name = new PersonNameInput(),
        #[Assert\Length(max: 100)]
        public ?string $jobTitle = null,
        #[Assert\Valid]
        public ContactDetailsInput $contactDetails = new ContactDetailsInput(),
    ) {
    }
}
