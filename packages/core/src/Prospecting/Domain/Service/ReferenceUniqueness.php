<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\ReferenceAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;

/**
 * A link to a tool outside designates one organization of the prospecting base of an account; other accounts may
 * note it too. A rule on the whole base, out of reach of one organization alone.
 */
final readonly class ReferenceUniqueness
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @throws ReferenceAlreadyUsed
     */
    public function ensureAvailableFor(Organization $organization): void
    {
        foreach ($organization->references()->all() as $reference) {
            $holder = $this->organizations->withReference($organization->accountId(), $reference, except: $organization->id());

            if (null !== $holder) {
                throw ReferenceAlreadyUsed::by($holder->fullName());
            }
        }
    }
}
