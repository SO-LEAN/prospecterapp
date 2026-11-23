<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Contact\EmailAlreadyUsed;

/**
 * Two organizations in an account's prospecting base cannot share an email. Other accounts may prospect the same
 * organization. The rule covers the whole base, out of one organization's reach.
 */
final readonly class EmailUniqueness
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    /**
     * @throws EmailAlreadyUsed
     */
    public function ensureAvailableFor(Organization $organization): void
    {
        $email = $organization->contactDetails()->email;

        if (null !== $email && $this->organizations->isEmailUsed($organization->accountId(), $email, except: $organization->id())) {
            throw EmailAlreadyUsed::byAnother();
        }
    }
}
