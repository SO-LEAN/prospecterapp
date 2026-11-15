<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;

/**
 * Port: what Connectors asks of a provider about the access a member gives it (OAuth 2, ADR 0120), through the client
 * of the account of the member (ADR 0125), implemented by the API.
 */
interface ProviderAccess
{
    /**
     * The access the provider grants the client for the code of authorization it gave the member, with its verifier
     * (PKCE).
     *
     * @throws AccessNotGranted
     */
    public function exchange(ClientCredentials $client, string $code, string $codeVerifier): Grant;

    /**
     * False once the provider refuses the token: revoked by the member, expired, or replaced too many times.
     */
    public function stillGrants(ClientCredentials $client, RefreshToken $refreshToken): bool;

    /**
     * Never fails: an access the provider could not revoke is forgotten all the same. A token is revoked without its
     * client, which may be gone.
     */
    public function revoke(Provider $provider, RefreshToken $refreshToken): void;
}
