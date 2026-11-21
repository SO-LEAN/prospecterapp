<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CompareWithContact;

/**
 * What completing a contact with what a client knows of them would fill, and what it gives otherwise than noted
 * (ADR 0177), before completing them.
 */
final readonly class CompareWithContact
{
    /**
     * @param list<string> $links the addresses of their links
     */
    public function __construct(
        public string $readerId,
        public string $id,
        public string $civility,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public array $links,
    ) {
    }
}
