<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The published language for a person's name (ADR 0045, 0127) and job title. A campaign also names the person by their
 * job title (ADR 0144). Both are unmasked, or masked for a reader without the right to read personal data, as
 * PersonalData shows them. The title is unmasked like the name (ADR 0129): ms, mr, or x while it is unknown. The e-mail
 * and the phone (E.164) are given only to a reader who reads them unmasked. A calendar uses them to invite whoever
 * booked its member.
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
