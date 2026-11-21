<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ContactLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        public ?LinkResource $rename = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeJobTitle = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeCivility = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $changeContactDetails = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $forget = null,
        #[OA\Property(description: 'The exchanges with the contact, the most recent first (ADR 0074)', nullable: false)]
        public ?LinkResource $exchanges = null,
        #[OA\Property(description: 'The to-dos for the contact (ADR 0113)', nullable: false)]
        public ?LinkResource $toDos = null,
        #[OA\Property(description: 'The tags put on the contact by hand, kept by Campaigns (ADR 0138)', nullable: false)]
        public ?LinkResource $tags = null,
        #[OA\Property(description: 'The organization proposed to attach the contact to, while they wait for the reader to attach them, with the right to manage personal data (ADR 0146)', nullable: false)]
        public ?LinkResource $proposedOrganization = null,
        #[OA\Property(description: 'Catch up the e-mails the mailbox of Gmail of the reader exchanged with the contact, while its import is active and the contact has an e-mail, with the right to manage personal data', nullable: false)]
        public ?LinkResource $catchUpGmail = null,
    ) {
    }
}
