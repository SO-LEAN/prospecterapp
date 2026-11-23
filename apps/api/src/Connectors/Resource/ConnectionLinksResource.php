<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A member disconnects their connection, whatever its state. They reconnect it through a new authorization. They push
 * again their exchanges that the provider refused.
 */
final readonly class ConnectionLinksResource
{
    public function __construct(
        public LinkResource $disconnect,
        #[OA\Property(description: 'Ask the provider for a new authorization. Present while a Prospecter client at the provider serves the account', nullable: false)]
        public ?LinkResource $reconnect = null,
        #[OA\Property(description: 'Push again each upcoming exchange that the provider refused. Present while there is one', nullable: false)]
        public ?LinkResource $retryFailed = null,
        #[OA\Property(description: 'Ask Google for access to my mailbox, to import my e-mails. Present while the account has a Gmail topic, I may manage personal data, and the import is not active', nullable: false)]
        public ?LinkResource $importGmail = null,
        #[OA\Property(description: 'Stop my e-mail import. Present while the import is not off', nullable: false)]
        public ?LinkResource $stopGmailImport = null,
    ) {
    }
}
