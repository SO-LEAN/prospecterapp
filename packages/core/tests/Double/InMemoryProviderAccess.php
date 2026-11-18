<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * A provider as the scenarios script it: a client serving the account unless a scenario takes it away, a code it gave for an account, granting a token of its own once, the
 * tokens it refuses since, and those it revoked: as Google does, revoking a token revokes the grant of its account,
 * every token given for it so far.
 */
final class InMemoryProviderAccess implements ProviderAccess
{
    /** @var array<string, Grant> by code */
    private array $codes = [];
    /** @var array<string, true> by token */
    private array $refused = [];
    /** @var list<string> */
    private array $revoked = [];
    /** @var array<string, string> the account each token was given for, by token */
    private array $accounts = [];
    private int $tokens = 0;
    private ?ClientCredentials $lastExchangedBy = null;
    private ?ClientCredentials $lastCheckedBy = null;

    /**
     * The code the provider gives the browser of the member who grants the access to their account.
     */
    public function grant(string $account, Scope ...$scopes): string
    {
        $code = sprintf('code-%d', ++$this->tokens);
        $token = sprintf('token-%d', $this->tokens);
        $this->codes[$code] = new Grant(new Email($account), new RefreshToken($token), ...$scopes);
        $this->accounts[$token] = $account;

        return $code;
    }

    public function tokenGrantedFor(string $code): string
    {
        return ($this->codes[$code] ?? throw new \LogicException(sprintf('No code %s given', $code)))->refreshToken->value;
    }

    public function accountOf(string $token): string
    {
        return $this->accounts[$token] ?? throw new \LogicException(sprintf('No token %s given', $token));
    }

    public function exchange(ClientCredentials $client, string $code, string $codeVerifier): Grant
    {
        $this->lastExchangedBy = $client;
        $grant = $this->codes[$code] ?? throw AccessNotGranted::by($client->provider);
        unset($this->codes[$code]);

        return $grant;
    }

    public function refuseEveryToken(): void
    {
        for ($token = 1; $token <= $this->tokens; ++$token) {
            $this->refused[sprintf('token-%d', $token)] = true;
        }
    }

    public function stillGrants(ClientCredentials $client, RefreshToken $refreshToken): bool
    {
        $this->lastCheckedBy = $client;

        return !isset($this->refused[$refreshToken->value]) && !$this->hasRevoked($refreshToken->value);
    }

    public function revoke(Provider $provider, RefreshToken $refreshToken): void
    {
        $account = $this->accounts[$refreshToken->value] ?? null;
        $this->revoked = [...$this->revoked, ...array_keys(array_filter($this->accounts, static fn (string $of): bool => $of === $account))];
    }

    public function hasRevoked(string $token): bool
    {
        return in_array($token, $this->revoked, true);
    }

    public function lastExchangedBy(): ?ClientCredentials
    {
        return $this->lastExchangedBy;
    }

    public function lastCheckedBy(): ?ClientCredentials
    {
        return $this->lastCheckedBy;
    }
}
