<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * A provider as the scenarios script it: a code it gave for an account, granting a token of its own once, the
 * tokens it refuses since, and those it revoked.
 */
final class InMemoryProviderAccess implements ProviderAccess
{
    /** @var array<string, Grant> by code */
    private array $codes = [];
    /** @var array<string, true> by token */
    private array $refused = [];
    /** @var list<string> */
    private array $revoked = [];
    private int $tokens = 0;

    /**
     * The code the provider gives the browser of the member who grants the access to their account.
     */
    public function grant(string $account, bool $withTheCalendar = true): string
    {
        $code = sprintf('code-%d', ++$this->tokens);
        $this->codes[$code] = new Grant(new Email($account), new RefreshToken(sprintf('token-%d', $this->tokens)), $withTheCalendar);

        return $code;
    }

    public function tokenGrantedFor(string $code): string
    {
        return ($this->codes[$code] ?? throw new \LogicException(sprintf('No code %s given', $code)))->refreshToken->value;
    }

    public function exchange(Provider $provider, string $code, string $codeVerifier): Grant
    {
        $grant = $this->codes[$code] ?? throw AccessNotGranted::by($provider);
        unset($this->codes[$code]);

        return $grant;
    }

    public function refuseEveryToken(): void
    {
        for ($token = 1; $token <= $this->tokens; ++$token) {
            $this->refused[sprintf('token-%d', $token)] = true;
        }
    }

    public function stillGrants(Provider $provider, RefreshToken $refreshToken): bool
    {
        return !isset($this->refused[$refreshToken->value]) && !$this->hasRevoked($refreshToken->value);
    }

    public function revoke(Provider $provider, RefreshToken $refreshToken): void
    {
        $this->revoked[] = $refreshToken->value;
    }

    public function hasRevoked(string $token): bool
    {
        return in_array($token, $this->revoked, true);
    }
}
