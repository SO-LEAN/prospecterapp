<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;

/**
 * Port: what Connectors asks a provider about the access that a member gives (OAuth 2, ADR 0120). Connectors asks
 * through the client of the member's account (ADR 0125). The API implements this port.
 */
interface ProviderAccess
{
    /**
     * The access that the provider grants the client for the authorization code it gave the member. The code comes with
     * its verifier (PKCE).
     *
     * @throws AccessNotGranted
     */
    public function exchange(ClientCredentials $client, string $code, string $codeVerifier): Grant;

    /**
     * False once the provider refuses the token. The member revoked it, it expired, or it was replaced too many times.
     */
    public function stillGrants(ClientCredentials $client, RefreshToken $refreshToken): bool;

    /**
     * Never fails: an access that the provider could not revoke is forgotten all the same. A token is revoked without
     * its client, because the client may be gone.
     */
    public function revoke(Provider $provider, RefreshToken $refreshToken): void;
}
