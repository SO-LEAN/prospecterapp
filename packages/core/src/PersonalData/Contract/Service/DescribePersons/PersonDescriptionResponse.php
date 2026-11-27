<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\DescribePersons;

/**
 * The published language for a person as a list shows them (ADR 0045, 0217). A reader without the right to read
 * personal data reads them masked: stars hide their job title, their e-mail, their phone and their links. Their name
 * and their title stay unmasked (ADR 0129). The title is ms, mr, or x while it is unknown.
 */
final readonly class PersonDescriptionResponse
{
    /**
     * @param list<LinkResponse> $links their links to outside tools, or a single link of stars when masked
     */
    public function __construct(
        public string $personId,
        public bool $masked,
        public ?string $firstName,
        public ?string $lastName,
        public string $title,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public array $links,
    ) {
    }
}
