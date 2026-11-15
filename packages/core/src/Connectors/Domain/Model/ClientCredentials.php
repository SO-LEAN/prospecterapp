<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\IncompleteClient;

/**
 * What a provider gave an account for Prospecter to ask it in the name of its members: the id of its client and its
 * secret (OAuth 2, ADR 0125). A token the provider grants is valid only with the client it was granted to.
 */
final readonly class ClientCredentials
{
    /**
     * @throws IncompleteClient
     */
    public function __construct(
        public Provider $provider,
        public string $id,
        #[\SensitiveParameter]
        public string $secret,
    ) {
        if ('' === $id) {
            throw IncompleteClient::withoutItsId();
        }

        if ('' === $secret) {
            throw IncompleteClient::withoutItsSecret();
        }
    }
}
