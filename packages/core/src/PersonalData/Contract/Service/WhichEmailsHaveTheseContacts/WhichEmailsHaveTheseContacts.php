<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts;

/**
 * Which addresses these contacts have, as the member reads them: asked about a set, a single contact being a set of
 * one (ADR 0089), to catch up the e-mails exchanged with them.
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
