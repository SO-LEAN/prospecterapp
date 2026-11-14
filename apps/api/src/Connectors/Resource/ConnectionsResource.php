<?php

namespace App\Connectors\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * My connections to the services outside, Google the one so far: none until I connect.
 */
final readonly class ConnectionsResource
{
    /**
     * @param list<ConnectionResource> $connections
     */
    public function __construct(
        public array $connections,
        #[SerializedName('_links')]
        public ConnectionsLinksResource $links,
    ) {
    }
}
