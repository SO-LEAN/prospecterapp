<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptContactNames implements NameContactsOutput
{
    /**
     * @var list<ContactNameResponse>
     */
    public private(set) array $names = [];

    public function present(array $names): void
    {
        $this->names = $names;
    }
}
