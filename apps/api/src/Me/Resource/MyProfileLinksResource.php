<?php

declare(strict_types=1);

namespace App\Me\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The links PersonalData offers on my profile. My account's composition copies them into `AccountLinksResource`
 * (ADR 0217).
 */
final readonly class MyProfileLinksResource
{
    public function __construct(
        #[Offers('api_me_change_name')]
        public ?LinkResource $changeMyName = null,
        #[Offers('api_me_change_contact_details')]
        public ?LinkResource $changeMyContactDetails = null,
        #[Offers('api_me_change_picture')]
        public ?LinkResource $changeMyPicture = null,
    ) {
    }
}
