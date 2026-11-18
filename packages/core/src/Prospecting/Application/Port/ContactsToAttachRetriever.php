<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the contacts to attach as their holders and the erasure see them, without restoring them (ADR 0088, 0146).
 */
interface ContactsToAttachRetriever
{
    /**
     * Those the member holds, the first to be erased first.
     *
     * @return list<ContactToAttachInList>
     */
    public function heldBy(MemberId $holder): array;

    /**
     * The person waiting for this member to attach them; none when attached, erased, or held by another.
     */
    public function findHeldBy(MemberId $holder, PersonId $person): ?ContactToAttachInList;

    /**
     * The persons of every account left unattached at this moment, to erase.
     *
     * @return list<string>
     */
    public function leftUnattachedAt(\DateTimeImmutable $at): array;
}
