<?php

namespace App\Shared\Persistence\Journal;

use Doctrine\ORM\Mapping as ORM;

/**
 * What happened, who did it and when (ADR 0043), in the database of the context it happened in: identities
 * only, never personal data, so that the journal may be kept after an erasure.
 */
#[ORM\Entity]
#[ORM\Table(name: 'journal')]
#[ORM\Index(columns: ['subject_id'])]
final readonly class JournalEntry
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        public string $id,
        #[ORM\Column(type: 'moment')]
        public \DateTimeImmutable $occurredAt,
        #[ORM\Column(type: 'guid')]
        public string $accountId,
        #[ORM\Column(type: 'guid')]
        public string $memberId,
        #[ORM\Column(length: 100)]
        public string $event,
        #[ORM\Column(type: 'guid')]
        public string $subjectId,
    ) {
    }
}
