<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;

/**
 * The links PersonalData offers on a contact: what the reader may change on the person, or forget. The contact's
 * composition copies them into `ContactLinksResource` (ADR 0217).
 */
final readonly class ContactProfileLinksResource
{
    public function __construct(
        #[Offers('api_contacts_rename')]
        public ?LinkResource $rename = null,
        #[Offers('api_contacts_change_job_title')]
        public ?LinkResource $changeJobTitle = null,
        #[Offers('api_contacts_change_title')]
        public ?LinkResource $changeTitle = null,
        #[Offers('api_contacts_change_contact_details')]
        public ?LinkResource $changeContactDetails = null,
        #[Offers('api_contacts_forget')]
        public ?LinkResource $forget = null,
    ) {
    }
}
