<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptContacts implements WhichAreContactsOutput
{
    /**
     * @var list<string>
     */
    public private(set) array $personIds = [];

    /**
     * @param list<string> $personIds
     */
    public function present(array $personIds): void
    {
        $this->personIds = $personIds;
    }
}
