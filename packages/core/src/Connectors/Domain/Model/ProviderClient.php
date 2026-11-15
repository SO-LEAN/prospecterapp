<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Event\ClientConfigured;
use Prospecter\Core\Connectors\Domain\Event\ClientRemoved;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: the client of Prospecter an account made at a provider, in its own space there, the one its members
 * connect through; one per account and provider (ADR 0125).
 */
final class ProviderClient implements ReleasesEvents
{
    use RecordsEvents;

    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly AccountId $accountId,
        private ClientCredentials $credentials,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function configure(AccountId $accountId, ClientCredentials $credentials, MemberId $by, \DateTimeImmutable $at): self
    {
        $client = new self($accountId, $credentials, $at);
        $client->recordThat(new ClientConfigured($accountId, $by, $at));

        return $client;
    }

    /**
     * Another client, or a new secret of the same one: a token granted to a former client is not valid with another.
     */
    public function replaceBy(ClientCredentials $credentials, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->credentials = $credentials;
        $this->modifiedAt = $at;
        $this->recordThat(new ClientConfigured($this->accountId, $by, $at));
    }

    public function removeBy(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->recordThat(new ClientRemoved($this->accountId, $by, $at));
    }

    public function credentials(): ClientCredentials
    {
        return $this->credentials;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function provider(): Provider
    {
        return $this->credentials->provider;
    }
}
