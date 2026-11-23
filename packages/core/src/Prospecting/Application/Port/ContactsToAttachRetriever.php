<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the contacts to attach as their holders and their erasure see them, without restoring them (ADR 0088, 0146).
 */
interface ContactsToAttachRetriever
{
    /**
     * Returns the contacts to attach that the member holds. The first one to be erased comes first.
     *
     * @return list<ContactToAttachInList>
     */
    public function heldBy(MemberId $holder): array;

    /**
     * Returns the person who waits for this member to attach them. It returns none when the person is attached, erased,
     * or held by another member.
     */
    public function findHeldBy(MemberId $holder, ContactId $person): ?ContactToAttachInList;

    /**
     * Returns the persons that are still unattached at this moment, in every account. They are to be erased.
     *
     * @return list<string>
     */
    public function leftUnattachedAt(\DateTimeImmutable $at): array;
}
