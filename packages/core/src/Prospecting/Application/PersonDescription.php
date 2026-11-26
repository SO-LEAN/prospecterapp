<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\SharedKernel\Application\Response\ExternalLink;

/**
 * A person that a view of Prospecting shows, a contact or a member, as PersonalData lets its reader read them
 * (ADR 0217). A reader without the right to read personal data reads them masked: stars hide their job title, their
 * e-mail, their phone and their links. Their name and their title stay unmasked.
 */
final readonly class PersonDescription
{
    /**
     * @param string             $title ms, mr, or x while it is unknown
     * @param list<ExternalLink> $links their links to outside tools, or a single link of stars when masked
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
