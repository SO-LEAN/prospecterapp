<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The link Connectors offers on a contact: reimport the e-mails the reader's Gmail mailbox exchanged with them. The
 * contact's composition copies it into `ContactLinksResource` (ADR 0217).
 */
final readonly class ContactMailLinksResource
{
    public function __construct(
        #[Offers('api_contacts_reimport_gmail')]
        public ?LinkResource $reimportGmail = null,
    ) {
    }
}
