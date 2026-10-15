<?php

namespace App\Shared\Resource;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;

final readonly class ContactDetailsResource
{
    public function __construct(
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
    ) {
    }

    public static function from(ContactDetails $contactDetails): self
    {
        return new self($contactDetails->email?->value, $contactDetails->phoneNumber, $contactDetails->language);
    }
}
