<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The published language of the name of a person (ADR 0045, 0127): in clear, or masked for a reader without the right
 * to read personal data, as PersonalData shows it.
 */
final readonly class ContactNameResponse
{
    public function __construct(
        public string $personId,
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}
