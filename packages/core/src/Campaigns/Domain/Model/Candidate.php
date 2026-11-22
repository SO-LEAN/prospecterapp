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
        public Addressee $addressee,
    ) {
    }
}
