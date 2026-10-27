<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

use Prospecter\Core\PersonalData\Application\PersonResponse;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptCallingContacts implements RecognizeCallingContactOutput
{
    /**
     * @var list<PersonResponse>
     */
    public private(set) array $contacts;

    /**
     * @param list<PersonResponse> $contacts the contacts having the number
     */
    public function present(array $contacts): void
    {
        $this->contacts = $contacts;
    }
}
