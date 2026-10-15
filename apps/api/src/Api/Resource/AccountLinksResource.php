<?php

namespace App\Api\Resource;

/**
 * One link per intention the signed-in user may carry out on their account now; null when they may not.
 */
final readonly class AccountLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $changeMyPassword,
        public ?LinkResource $changeMyName = null,
        public ?LinkResource $changeMyUserName = null,
        public ?LinkResource $changeMyContactDetails = null,
        public ?LinkResource $changeMyPicture = null,
        public ?LinkResource $reviseMyOrganizationIdentity = null,
        public ?LinkResource $changeMyOrganizationLogo = null,
    ) {
    }
}
