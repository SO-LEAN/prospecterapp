<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The links Campaigns offers on a campaign's next recipient: log the exchange held with them, or dismiss them. The
 * next recipient's composition copies them into `RecipientLinksResource` (ADR 0217), which also offers to remove a
 * recipient from a frozen campaign.
 */
final readonly class NextRecipientLinksResource
{
    public function __construct(
        #[Offers('api_campaigns_note_exchange')]
        public LinkResource $noteExchange,
        #[Offers('api_campaigns_dismiss_recipient')]
        public LinkResource $dismiss,
    ) {
    }
}
