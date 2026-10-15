<?php

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The person to attach, among the contacts of my organization.
 */
final readonly class ContactInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $personId = '',
    ) {
    }
}
