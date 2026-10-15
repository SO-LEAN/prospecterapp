<?php

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * One link per intention the signed-in user may carry out on their account now; absent when they may not.
 */
final readonly class AccountLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyName = null,
        #[OA\Property(nullable: false)]
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
