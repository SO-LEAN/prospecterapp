<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptMailCatchUpIntentsOfMany implements WhichMailCatchUpIntentsOutput
{
    /**
     * @var list<MailCatchUpIntentsResponse>
     */
    public private(set) array $contacts = [];
    /**
     * @var list<MailCatchUpIntentsResponse>
     */
    public private(set) array $organizations = [];

    public function present(array $contacts, array $organizations): void
    {
        $this->contacts = $contacts;
        $this->organizations = $organizations;
    }
}
