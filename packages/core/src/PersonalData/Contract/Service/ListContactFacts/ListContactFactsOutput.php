<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\ListContactFacts;

interface ListContactFactsOutput
{
    /**
     * @param list<ContactFactsResponse> $facts none for an unknown account
     * @param string|null                $next  the cursor of the next slice, null when this one is the last
     */
    public function present(array $facts, ?string $next): void;
}
