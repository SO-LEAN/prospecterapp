<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptContactFacts implements ListContactFactsOutput
{
    /**
     * @var list<ContactFactsResponse>
     */
    public private(set) array $facts = [];
    public private(set) ?string $next = null;

    /**
     * @param list<ContactFactsResponse> $facts
     */
    public function present(array $facts, ?string $next): void
    {
        $this->facts = $facts;
        $this->next = $next;
    }
}
