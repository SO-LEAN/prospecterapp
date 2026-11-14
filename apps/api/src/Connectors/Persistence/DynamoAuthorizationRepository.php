<?php

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Psr\Clock\ClockInterface;

/**
 * The authorizations waiting for the answer of their provider in the table of Connectors (ADR 0120):
 * `AUTHORIZATION#<state>` / `AUTHORIZATION`, `expiresAt` the epoch the table forgets it at, counted from now, the real
 * one: the rule refusing it once expired is that of the core.
 */
final readonly class DynamoAuthorizationRepository implements AuthorizationRepository
{
    private const string KEPT_FOR = '+1 hour';

    public function __construct(private Table $connectorsTable, private ClockInterface $clock)
    {
    }

    public function keep(PendingAuthorization $authorization): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->putting([
            'PK' => ConnectorsKeys::authorization($authorization->state),
            'SK' => ConnectorsKeys::AUTHORIZATION,
            'authorization' => [
                'state' => $authorization->state,
                'member' => $authorization->member->value,
                'provider' => $authorization->provider->value,
                'codeVerifier' => $authorization->codeVerifier,
                'expiresAt' => Documents::moment($authorization->expiresAt),
            ],
            'expiresAt' => $this->clock->now()->modify(self::KEPT_FOR)->getTimestamp(),
        ])]);
    }

    public function find(string $state): ?PendingAuthorization
    {
        $document = $this->connectorsTable->item(ConnectorsKeys::authorization($state), ConnectorsKeys::AUTHORIZATION)['authorization'] ?? null;

        return null === $document ? null : Hydrator::restore(PendingAuthorization::class, [
            'state' => $document['state'],
            'member' => new MemberId($document['member']),
            'provider' => Provider::from($document['provider']),
            'codeVerifier' => $document['codeVerifier'],
            'expiresAt' => Documents::momentOf($document['expiresAt']),
        ]);
    }

    public function forget(PendingAuthorization $authorization): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::authorization($authorization->state), ConnectorsKeys::AUTHORIZATION)]);
    }
}
