<?php

namespace App\Api\Resource;

use OpenApi\Attributes as OA;

/**
 * One link per intention the signed-in user may carry out on their account now; absent when they may not.
 */
final readonly class AccountLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $changeMyPassword,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyName = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyUserName = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyContactDetails = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyPicture = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $reviseMyOrganizationIdentity = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyOrganizationLogo = null,
    ) {
    }
}
