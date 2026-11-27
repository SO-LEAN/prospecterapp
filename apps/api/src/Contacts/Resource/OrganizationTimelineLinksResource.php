<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The links Prospecting offers on an organization's timeline. The timeline's composition copies them into
 * `TimelineLinksResource`, beside the catch-up that Connectors offers (ADR 0217).
 */
final readonly class OrganizationTimelineLinksResource
{
    public function __construct(
        public LinkResource $self,
        public ?LinkResource $next = null,
        #[Offers('api_contacts_note_exchange', 'api_organizations_note_exchange')]
        public ?LinkResource $noteExchange = null,
        #[Offers('api_contacts_plan_exchange', 'api_organizations_plan_exchange')]
        public ?LinkResource $planExchange = null,
        public ?LinkResource $toRedact = null,
    ) {
    }
}
