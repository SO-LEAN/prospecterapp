<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the persons a view shows, as PersonalData lets the reader read them (ADR 0217). Accounts tells each member's
 * person. PersonalData describes them all in one call, and decides what the reader reads in clear. A person or a member
 * may be asked about more than once.
 */
interface PersonDescriptionRetriever
{
    /**
     * @param list<ContactId> $contacts
     * @param list<MemberId>  $members
     */
    public function shownTo(MemberId $reader, array $contacts, array $members): PersonsShown;
}
