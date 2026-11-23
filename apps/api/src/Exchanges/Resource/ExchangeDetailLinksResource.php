<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class ExchangeDetailLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false, description: 'The interlocutor\'s card: the contact, or the organization itself. Absent for a forgotten contact')]
        public ?LinkResource $interlocutor = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $correctExchange = null,
        #[OA\Property(nullable: false, description: 'Corrects its text only. Offered without the right to manage personal data too')]
        public ?LinkResource $correctText = null,
        #[OA\Property(nullable: false, description: 'Corrects a meeting\'s place or a video call\'s link, planned or done. Offered without the right to manage personal data too')]
        public ?LinkResource $correctVenue = null,
        #[OA\Property(nullable: false, description: 'Once its contact is forgotten, a correction of its facts or its text with `redacted` declares it redacted. Offered until it is redacted, without the right to manage personal data too')]
        public ?LinkResource $redact = null,
        #[OA\Property(nullable: false)]
        public ?LinkResource $eraseExchange = null,
        #[OA\Property(nullable: false, description: 'Attaches a file sent with an upload form, while the exchange has room for one')]
        public ?LinkResource $attachFile = null,
        #[OA\Property(nullable: false, description: 'Marks a planned exchange done with its report, once its time has come. Offered to its holder only')]
        public ?LinkResource $markDone = null,
        #[OA\Property(nullable: false, description: 'Marks a planned exchange missed, once its time has come. Offered to its holder only')]
        public ?LinkResource $markMissed = null,
        #[OA\Property(nullable: false, description: 'Moves a planned exchange to another moment in the future. It keeps its duration unless a new one is given. Offered to its holder only')]
        public ?LinkResource $move = null,
        #[OA\Property(nullable: false, description: 'Only in a move\'s response: moves the exchange back to the moment it was moved from, while that moment is still in the future. Offered to its holder only')]
        public ?LinkResource $moveBack = null,
        #[OA\Property(nullable: false, description: 'Cancels a planned exchange. Offered to its holder only')]
        public ?LinkResource $cancel = null,
        #[OA\Property(nullable: false, description: 'The campaign it was logged in, while the campaign exists. Offered with the right to manage personal data only')]
        public ?LinkResource $campaign = null,
        #[OA\Property(nullable: false, description: 'Opens an imported exchange\'s message in a new tab of the provider\'s mailbox. Offered once the message\'s id in the mailbox is kept (ADR 0174)')]
        public ?LinkResource $openInMailbox = null,
    ) {
    }
}
