<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

/**
 * A member checks their connections with their providers. They connect Google by requesting an authorization. An
 * administrator configures the account's Google client.
 */
final readonly class ConnectionsLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[Offers('api_me_connections_check')]
        public LinkResource $check,
        #[OA\Property(description: 'Ask Google for an authorization. Present while a Prospecter client at Google serves the account', nullable: false)]
        #[Offers('api_me_connections_google_authorize')]
        public ?LinkResource $connectGoogle = null,
        #[OA\Property(description: 'The account\'s Google client. Present for an administrator', nullable: false)]
        public ?LinkResource $googleClient = null,
    ) {
    }
}
