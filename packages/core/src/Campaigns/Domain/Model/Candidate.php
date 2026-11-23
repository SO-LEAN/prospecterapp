<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * A contact that a member adds to a campaign by hand. It holds who they are, the tags they carry and what a message
 * calls them.
 */
final readonly class Candidate
{
    public function __construct(
        public ContactId $contact,
        public ContactTags $tags,
        private Addressee $addressee,
    ) {
    }

    /** What a refusal calls them: their first name and last name, as far as they are known. */
    #[\NoDiscard]
    public function name(): string
    {
        return $this->addressee->name();
    }
}
