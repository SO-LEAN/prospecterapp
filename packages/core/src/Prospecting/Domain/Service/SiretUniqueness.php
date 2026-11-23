<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\SiretAlreadyUsed;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;

/**
 * An establishment is one organization in an account's prospecting base. Other accounts may prospect it too. The rule
 * covers the whole base, out of one organization's reach.
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
