<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class SearchProspectedOrganizationsResponse
{
    /**
     * @param list<FoundOrganization> $organizations
     * @param string|null             $next          the cursor of the next slice, null when this one is the last
     */
    private function __construct(
        public array $organizations,
        public ?string $next,
    ) {
    }

    /**
     * @param Slice<Organization> $slice
     */
    public static function of(Slice $slice): self
    {
        return new self(array_map(FoundOrganization::of(...), $slice->content), $slice->next);
    }
}
