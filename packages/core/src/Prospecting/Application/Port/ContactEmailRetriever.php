<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads a contact's e-mail as PersonalData lets the member read it, in clear (ADR 0146, 0217). Prospecting keeps no
 * e-mail of a contact.
 */
interface ContactEmailRetriever
{
    /**
     * None when the contact has no e-mail.
     *
     * @throws UnknownContact unless the member reads the contact in clear
     */
    public function emailOf(MemberId $reader, ContactId $contact): ?string;
}
