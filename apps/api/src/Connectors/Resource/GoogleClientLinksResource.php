<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

final readonly class GoogleClientLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(description: 'Configure the client, or replace it with another one, with its id and its secret')]
        public LinkResource $configure,
        #[OA\Property(description: 'Remove the client. Present while the account has one', nullable: false)]
        public ?LinkResource $remove = null,
        #[OA\Property(description: 'Name the Gmail topic and the service account that pushes its notifications. Present while the account has a client', nullable: false)]
        public ?LinkResource $configureGmailNotifications = null,
        #[OA\Property(description: 'Remove the Gmail topic. Present while the account has one', nullable: false)]
        public ?LinkResource $removeGmailNotifications = null,
    ) {
    }
}
