<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact a member adds to a campaign by hand: who they are, the tags they carry and what a message names them by.
 */
final readonly class Candidate
{
    public function __construct(
        public PersonId $contact,
        public ContactTags $tags,
        private Addressee $addressee,
    ) {
    }

    /**
     * What a refusal names them by: their first name and their last name, those known.
     */
    #[\NoDiscard]
    public function name(): string
    {
        return $this->addressee->name();
    }
}
