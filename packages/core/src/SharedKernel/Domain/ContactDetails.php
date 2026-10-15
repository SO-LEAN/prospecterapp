<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

final readonly class ContactDetails
{
    public function __construct(
        public ?Email $email = null,
        public ?string $phoneNumber = null,
        public ?string $language = null,
    ) {
    }
}
