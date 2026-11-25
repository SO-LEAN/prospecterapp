<?php

declare(strict_types=1);

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

/**
 * One link per intent that the signed-in user may carry out on their account now. A link is absent when they may not.
 */
final readonly class AccountLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        #[Offers('api_me_change_name')]
        public ?LinkResource $changeMyName = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_me_change_contact_details')]
        public ?LinkResource $changeMyContactDetails = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_me_change_picture')]
        public ?LinkResource $changeMyPicture = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_me_revise_organization_identity')]
        public ?LinkResource $reviseMyOrganizationIdentity = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_me_change_organization_logo')]
        public ?LinkResource $changeMyOrganizationLogo = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_me_relocate_organization')]
        public ?LinkResource $relocateMyOrganization = null,
        #[OA\Property(description: 'My team\'s campaigns. The link needs the right to manage personal data.', nullable: false)]
        public ?LinkResource $campaigns = null,
    ) {
    }
}
