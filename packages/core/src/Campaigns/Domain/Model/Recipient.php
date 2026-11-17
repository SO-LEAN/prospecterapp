<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact a frozen campaign keeps to be contacted, by its identity only: its name is PersonalData's.
 */
final readonly class Recipient
{
    public function __construct(public PersonId $contact, public RecipientStatus $status)
    {
    }

    public static function pending(PersonId $contact): self
    {
        return new self($contact, RecipientStatus::Pending);
    }

    #[\NoDiscard]
    public function is(PersonId $contact): bool
    {
        return $this->contact->equals($contact);
    }
}
