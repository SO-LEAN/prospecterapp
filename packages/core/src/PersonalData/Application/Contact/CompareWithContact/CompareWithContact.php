<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\CompareWithContact;

/**
 * Tells, before a contact is completed, which fields the completion would fill with what a client knows about them.
 * It also tells which given fields differ from the logged ones (ADR 0177).
 */
final readonly class CompareWithContact
{
    /**
     * @param list<string> $links their links' addresses
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
