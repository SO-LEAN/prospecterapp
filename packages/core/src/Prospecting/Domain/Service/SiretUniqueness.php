<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;

/**
 * An establishment is one organization of the prospecting base of an account; other accounts may prospect it too. A
 * rule on the whole base, out of reach of one organization alone.
 */
final readonly class SiretUniqueness
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @throws SiretAlreadyUsed
     */
    public function ensureAvailableFor(Organization $organization): void
    {
        $siret = $organization->siret();
        $holder = null === $siret ? null : $this->organizations->withSiret($organization->accountId(), $siret, except: $organization->id());

        if (null !== $holder) {
            throw SiretAlreadyUsed::by($holder->fullName());
        }
    }
}
