<?php

namespace App\Accounts\Input;

use Prospecter\Core\Accounts\Domain\PersonName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PersonNameInput
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public ?string $firstName = null,
        #[Assert\Length(max: 100)]
        public ?string $lastName = null,
    ) {
    }

    public function toPersonName(): PersonName
    {
        return new PersonName($this->firstName, $this->lastName);
    }
}
