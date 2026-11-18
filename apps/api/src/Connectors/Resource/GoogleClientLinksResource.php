<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class GoogleClientLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Configure the client, or another one, with its id and its secret')]
        public LinkResource $configure,
        #[OA\Property(description: 'Remove the client, while the account has one', nullable: false)]
        public ?LinkResource $remove = null,
        #[OA\Property(description: 'Name the topic of Gmail and the account of service pushing its notifications, while the account has a client', nullable: false)]
        public ?LinkResource $configureGmailNotifications = null,
        #[OA\Property(description: 'Remove the topic of Gmail, while the account has one', nullable: false)]
        public ?LinkResource $removeGmailNotifications = null,
    ) {
    }
}
