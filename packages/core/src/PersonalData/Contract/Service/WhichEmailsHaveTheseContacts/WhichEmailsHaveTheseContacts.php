<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts;

/**
 * Which addresses these contacts have, as the member reads them (ADR 0089). It asks about a set. A single contact is a
 * set of one. A client uses it to catch up on the e-mails exchanged with these contacts.
 */
final readonly class WhichEmailsHaveTheseContacts
{
    /**
     * @param list<string> $contactIds
     */
    public function __construct(
        public string $readerId,
        public array $contactIds,
    ) {
    }
}
