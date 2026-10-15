<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;

/**
 * Two organizations of the prospecting base of an account cannot share an email; other accounts may
 * prospect the same organization. A rule on the whole base, out of reach of one organization alone.
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
