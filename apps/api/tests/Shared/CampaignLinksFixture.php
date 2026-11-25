<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * A links resource whose declarations the tests of OffersMatchTheirLinks fill with the links they choose.
 */
final readonly class CampaignLinksFixture
{
    public function __construct(
        #[Offers('api_campaigns_revise')]
        public ?LinkResource $revise = null,
        public ?LinkResource $freeze = null,
        public ?LinkResource $recipients = null,
        public ?LinkResource $elsewhere = null,
        #[Offers('api_campaigns_start', 'api_campaigns_stop')]
        public ?LinkResource $startOrStop = null,
    ) {
    }
}
