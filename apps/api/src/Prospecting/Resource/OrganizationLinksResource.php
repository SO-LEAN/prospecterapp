<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
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
        #[OA\Property(nullable: false)]
        public ?LinkResource $addContact = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $stopProspecting = null,
        #[OA\Property(nullable: false, description: 'Its timeline: its exchanges and those of its contacts')]
        public ?LinkResource $exchanges = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $noteExchange = null,
    ) {
    }
}
