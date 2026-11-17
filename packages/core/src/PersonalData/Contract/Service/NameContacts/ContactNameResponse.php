<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The published language of the name of a person (ADR 0045, 0127), and of their job title, which a campaign names
 * them by too (ADR 0144): in clear, or masked for a reader without the right to read personal data, as PersonalData
 * shows them.
 */
final readonly class ContactNameResponse
{
    public function __construct(
        public string $personId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $jobTitle,
    ) {
    }
}
