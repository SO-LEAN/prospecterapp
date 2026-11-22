<?php

declare(strict_types=1);

namespace App\SignIn\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The metadata of the MCP server as a protected resource (RFC 9728), in its names.
 */
final readonly class ProtectedResourceMetadataResource
{
    /**
     * @param list<string> $authorizationServers
     */
    public function __construct(
        public string $resource,
        #[SerializedName('authorization_servers')]
        public array $authorizationServers,
    ) {
    }
}
