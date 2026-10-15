<?php

namespace App\Api\Resource;

/**
 * One link per intention the signed-in user may carry out on the organization now; null when it may not.
 */
final readonly class OrganizationLinksResource
{
    public function __construct(
        public LinkResource $self,
        public ?LinkResource $reviseLegalIdentity = null,
        public ?LinkResource $changeContactDetails = null,
        public ?LinkResource $relocate = null,
        public ?LinkResource $writeObservations = null,
        public ?LinkResource $attachToHolding = null,
        public ?LinkResource $detachFromHolding = null,
        public ?LinkResource $changeLogo = null,
        public ?LinkResource $removeLogo = null,
    ) {
    }
}
