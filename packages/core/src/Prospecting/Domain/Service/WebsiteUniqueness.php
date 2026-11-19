<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\WebsiteAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;

/**
 * A domain on the web is one organization of the prospecting base of an account; other accounts may
 * prospect it too. A rule on the whole base, out of reach of one organization alone.
 */
final readonly class WebsiteUniqueness
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @throws WebsiteAlreadyUsed
     */
    public function ensureAvailableFor(Organization $organization): void
    {
        $website = $organization->website();
        $holder = null === $website ? null : $this->organizations->withWebsiteOfDomain($organization->accountId(), $website, except: $organization->id());

        if (null !== $holder) {
            throw WebsiteAlreadyUsed::by($holder->fullName());
        }
    }
}
