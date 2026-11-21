<?php

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeDetailLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The card of the interlocutor: the contact, or the organization itself; absent for a forgotten contact')]
        public ?LinkResource $interlocutor = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $correctExchange = null,
        #[OA\Property(nullable: false, description: 'Its text only, offered without the right to manage personal data too')]
        public ?LinkResource $correctText = null,
        #[OA\Property(nullable: false, description: 'The place of a meeting or the link of a video call, planned or done; offered without the right to manage personal data too')]
        public ?LinkResource $correctVenue = null,
        #[OA\Property(nullable: false, description: 'Its contact forgotten, a correction of its facts or of its text with redacted declares it redacted, until it is; offered without the right to manage personal data too')]
        public ?LinkResource $redact = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $eraseExchange = null,
        #[OA\Property(nullable: false, description: 'Joins a file sent with the form of an upload, while the exchange has room for one')]
        public ?LinkResource $attachFile = null,
        #[OA\Property(nullable: false, description: 'Marks done, with its report, a planned exchange whose time has come; to its holder only')]
        public ?LinkResource $markDone = null,
        #[OA\Property(nullable: false, description: 'Marks missed a planned exchange whose time has come; to its holder only')]
        public ?LinkResource $markMissed = null,
        #[OA\Property(nullable: false, description: 'Moves a planned exchange to another moment to come, for a new duration or its own; to its holder only')]
        public ?LinkResource $move = null,
        #[OA\Property(nullable: false, description: 'In the response of a move only: moves the exchange back to the moment it was moved from, while that moment is to come; to its holder only')]
        public ?LinkResource $moveBack = null,
        #[OA\Property(nullable: false, description: 'Cancels a planned exchange; to its holder only')]
        public ?LinkResource $cancel = null,
        #[OA\Property(nullable: false, description: 'The campaign it was noted in, while it is kept; with the right to manage personal data only')]
        public ?LinkResource $campaign = null,
        #[OA\Property(nullable: false, description: 'Opens, in a new tab of the mailbox of the provider, the message an imported exchange comes from, once the id of its message in the mailbox is kept (ADR 0174)')]
        public ?LinkResource $openInMailbox = null,
    ) {
    }
}
