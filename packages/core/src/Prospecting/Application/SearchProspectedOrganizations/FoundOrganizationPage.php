<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * A slice of the organizations an account prospects, as a list shows them.
 */
final readonly class FoundOrganizationPage
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
     * @param Slice<FoundOrganization> $slice
     */
    public static function of(Slice $slice): self
    {
        return new self($slice->content, $slice->next);
    }
}
