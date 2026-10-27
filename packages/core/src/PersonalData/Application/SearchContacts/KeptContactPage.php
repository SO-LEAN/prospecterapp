<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptContactPage implements SearchContactsOutput
{
    public private(set) SearchContactsResponse $page;

    public function present(SearchContactsResponse $page): void
    {
        $this->page = $page;
    }
}
