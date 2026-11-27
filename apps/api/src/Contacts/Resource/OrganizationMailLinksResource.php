<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The link Connectors offers on an organization: reimport the e-mails the reader's Gmail mailbox exchanged with the
 * organization's own address. The organization's timeline composition copies it into `TimelineLinksResource`
 * (ADR 0217).
 */
final readonly class OrganizationMailLinksResource
{
    public function __construct(
        #[Offers('api_organizations_reimport_gmail')]
        public ?LinkResource $reimportGmail = null,
    ) {
    }
}
