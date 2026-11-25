<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class ContactLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        #[Offers('api_contacts_rename')]
        public ?LinkResource $rename = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_contacts_change_job_title')]
        public ?LinkResource $changeJobTitle = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_contacts_change_title')]
        public ?LinkResource $changeTitle = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_contacts_change_contact_details')]
        public ?LinkResource $changeContactDetails = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_contacts_forget')]
        public ?LinkResource $forget = null,
        #[OA\Property(description: 'The exchanges with the contact, the most recent first (ADR 0074)', nullable: false)]
        public ?LinkResource $exchanges = null,
        #[OA\Property(description: 'The to-dos for the contact (ADR 0113)', nullable: false)]
        public ?LinkResource $toDos = null,
        #[OA\Property(description: 'The tags put on the contact by hand. Campaigns keeps them (ADR 0138)', nullable: false)]
        public ?LinkResource $tags = null,
        #[OA\Property(description: 'The organization proposed to attach the contact to, while they wait for the reader to attach them. It needs the right to manage personal data (ADR 0146)', nullable: false)]
        public ?LinkResource $proposedOrganization = null,
        #[OA\Property(description: 'Reimport the e-mails that the reader\'s Gmail mailbox exchanged with the contact. It is there while the import is active and the contact has an e-mail, with the right to manage personal data', nullable: false)]
        #[Offers('api_contacts_reimport_gmail')]
        public ?LinkResource $reimportGmail = null,
    ) {
    }
}
