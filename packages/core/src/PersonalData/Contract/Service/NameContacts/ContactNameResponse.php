<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The published language of the name of a person (ADR 0045, 0127), and of their job title, which a campaign names
 * them by too (ADR 0144): in clear, or masked for a reader without the right to read personal data, as PersonalData
 * shows them; their civility in clear like their name (ADR 0129), ms, mr or x while unknown; their e-mail and their
 * phone (E.164) for a reader who reads them in clear only, a calendar inviting whoever booked its member.
 */
final readonly class ContactNameResponse
{
    public function __construct(
        public string $personId,
        public ?string $firstName,
        public ?string $lastName,
        public string $civility,
        public ?string $jobTitle,
        public ?string $email = null,
        public ?string $phoneNumber = null,
    ) {
    }
}
