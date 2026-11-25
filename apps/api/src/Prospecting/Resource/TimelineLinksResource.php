<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

final readonly class TimelineLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The next slice, absent when this one is the last')]
        public ?LinkResource $next = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_contacts_note_exchange', 'api_organizations_note_exchange')]
        public ?LinkResource $noteExchange = null,
        #[OA\Property(nullable: false, description: 'Plans an exchange to come')]
        #[Offers('api_contacts_plan_exchange', 'api_organizations_plan_exchange')]
        public ?LinkResource $planExchange = null,
        #[OA\Property(nullable: false, description: 'An organization\'s exchanges to redact, whose contact is forgotten. Absent while there is none')]
        public ?LinkResource $toRedact = null,
        #[OA\Property(nullable: false, description: 'Reimports the e-mails that the reader\'s Gmail mailbox exchanged with the organization\'s own address. Present while the import is active and the organization has an e-mail, and only with the right to manage personal data')]
        #[Offers('api_organizations_reimport_gmail')]
        public ?LinkResource $reimportGmail = null,
    ) {
    }

    public function withToRedact(?LinkResource $toRedact): self
    {
        return new self($this->self, $this->next, $this->noteExchange, $this->planExchange, $toRedact, $this->reimportGmail);
    }

    public function withReimportGmail(?LinkResource $reimportGmail): self
    {
        return new self($this->self, $this->next, $this->noteExchange, $this->planExchange, $this->toRedact, $reimportGmail);
    }
}
