<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptCallingContacts implements RecognizeCallingContactOutput
{
    /**
     * @var list<PersonCard>
     */
    public private(set) array $contacts;

    /**
     * @param list<PersonCard> $contacts the contacts having the number
     */
    public function present(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}
