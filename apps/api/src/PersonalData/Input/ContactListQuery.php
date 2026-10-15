<?php

namespace App\PersonalData\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactListQuery
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Length(max: 100)]
        public string $q = '',
    ) {
    }
}
