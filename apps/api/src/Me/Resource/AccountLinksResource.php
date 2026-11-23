<?php

declare(strict_types=1);

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * One link per intent that the signed-in user may carry out on their account now. A link is absent when they may not.
 */
final readonly class AccountLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyName = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyContactDetails = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyPicture = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $reviseMyOrganizationIdentity = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeMyOrganizationLogo = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $relocateMyOrganization = null,
        #[OA\Property(description: 'My team\'s campaigns. The link needs the right to manage personal data.', nullable: false)]
        public ?LinkResource $campaigns = null,
    ) {
    }
}
