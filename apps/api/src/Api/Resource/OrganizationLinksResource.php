<?php

namespace App\Api\Resource;

use OpenApi\Attributes as OA;

/**
 * One link per intention the signed-in user may carry out on the organization now; absent when it may not.
 */
final readonly class OrganizationLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        public ?LinkResource $reviseLegalIdentity = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeContactDetails = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $relocate = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $writeObservations = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $attachToHolding = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $detachFromHolding = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeLogo = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $removeLogo = null,
    ) {
    }
}
