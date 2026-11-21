<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class TimelineLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The next slice, absent when this one is the last')]
        public ?LinkResource $next = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $noteExchange = null,
        #[OA\Property(nullable: false, description: 'Plan an exchange to come, with the right to manage personal data')]
        public ?LinkResource $planExchange = null,
        #[OA\Property(nullable: false, description: 'The exchanges of an organization to redact, their contact forgotten, absent while there is none')]
        public ?LinkResource $toRedact = null,
        #[OA\Property(nullable: false, description: 'Catch up the e-mails the mailbox of Gmail of the reader exchanged with the address of the organization itself, while its import is active and the organization has an e-mail, with the right to manage personal data')]
        public ?LinkResource $catchUpGmail = null,
    ) {
    }

    public function withToRedact(?LinkResource $toRedact): self
    {
        return new self($this->self, $this->next, $this->noteExchange, $this->planExchange, $toRedact, $this->catchUpGmail);
    }

    public function withCatchUpGmail(?LinkResource $catchUpGmail): self
    {
        return new self($this->self, $this->next, $this->noteExchange, $this->planExchange, $this->toRedact, $catchUpGmail);
    }
}
