<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\Page;

final readonly class SearchProspectedOrganizationsResponse
{
    /**
     * @param list<FoundOrganization> $organizations
     */
    private function __construct(
        public int $currentPage,
        public int $total,
        public int $totalPages,
        public array $organizations,
    ) {
    }

    /**
     * @param Page<Organization> $page
     */
    public static function of(Page $page): self
    {
        return new self($page->number, $page->total, $page->totalPages, array_map(FoundOrganization::of(...), $page->content));
    }
}
