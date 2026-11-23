<?php

declare(strict_types=1);

namespace App\SignIn\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The MCP server's metadata as a protected resource (RFC 9728), with the RFC's field names.
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
