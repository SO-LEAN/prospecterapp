<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Domain\Organization\OrganizationRepository;
use Prospecter\Core\Domain\User\User;

final readonly class AccountResponses
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function of(User $me): AccountResponse
    {
        $myOrganization = $this->organizations->get($me->organizationId());

        return new AccountResponse(
            userName: $me->userName(),
            name: $me->name(),
            contactDetails: $me->contactDetails(),
            picture: $me->picture(),
            possibleIntents: $me->possibleIntents(),
            organizationIdentity: $myOrganization->legalIdentity(),
            organizationLogo: $myOrganization->logo(),
            organizationIntents: $myOrganization->possibleIntentsOf($me),
        );
    }
}
