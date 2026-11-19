<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\LinkedInPageAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;

/**
 * A page of a company on LinkedIn is one organization of the prospecting base of an account; other accounts may
 * prospect it too. A rule on the whole base, out of reach of one organization alone.
 */
final readonly class LinkedInPageUniqueness
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @throws LinkedInPageAlreadyUsed
     */
    public function ensureAvailableFor(Organization $organization): void
    {
        $page = $organization->linkedInPage();
        $holder = null === $page ? null : $this->organizations->withLinkedInPage($organization->accountId(), $page, except: $organization->id());

        if (null !== $holder) {
            throw LinkedInPageAlreadyUsed::by($holder->fullName());
        }
    }
}
