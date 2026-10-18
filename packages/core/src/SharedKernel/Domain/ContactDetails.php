<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

final readonly class ContactDetails
{
    public function __construct(
        public ?Email $email = null,
        public ?PhoneNumber $phoneNumber = null,
        public ?string $language = null,
    ) {
    }
}
