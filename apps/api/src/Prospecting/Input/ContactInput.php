<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The person to add, among my account's contacts.
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
