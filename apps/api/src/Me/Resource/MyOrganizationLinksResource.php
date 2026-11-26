<?php

declare(strict_types=1);

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The links Accounts offers on my account: what I may change on my organization, and my team's campaigns. My
 * account's composition copies them into `AccountLinksResource` (ADR 0217).
 */
final readonly class MyOrganizationLinksResource
{
    public function __construct(
        #[Offers('api_me_revise_organization_identity')]
        public ?LinkResource $reviseMyOrganizationIdentity = null,
        #[Offers('api_me_change_organization_logo')]
        public ?LinkResource $changeMyOrganizationLogo = null,
        #[Offers('api_me_relocate_organization')]
        public ?LinkResource $relocateMyOrganization = null,
        public ?LinkResource $campaigns = null,
    ) {
    }
}
