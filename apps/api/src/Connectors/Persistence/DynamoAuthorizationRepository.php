<?php

declare(strict_types=1);

namespace App\Connectors\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Psr\Clock\ClockInterface;

/**
 * The authorizations that wait for their provider's answer, in the Connectors table (ADR 0120). The key is
 * `AUTHORIZATION#<state>` / `AUTHORIZATION`. `expiresAt` is the epoch when the table forgets the item, counted from the
 * real current time. The core's rule refuses an expired authorization. The item also keeps the requested scopes
 * (ADR 0154).
 */
final readonly class DynamoAuthorizationRepository implements AuthorizationRepository
{
    private const string KEPT_FOR = '+1 hour';
    /** what an authorization stored before ADR 0154 asked for */
    private const array THE_CALENDAR = ['calendar'];

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
                'scopes' => array_map(static fn (Scope $scope): string => $scope->value, $authorization->scopes),
            ],
            'expiresAt' => $this->clock->now()->modify(self::KEPT_FOR)->getTimestamp(),
        ])]);
    }

    public function find(string $state): ?PendingAuthorization
    {
        $item = $this->connectorsTable->item(ConnectorsKeys::authorization($state), ConnectorsKeys::AUTHORIZATION);
        $document = null === $item ? null : Typed::document($item['authorization']);

        return null === $document ? null : Hydrator::restore(PendingAuthorization::class, [
            'state' => Typed::string($document['state']),
            'member' => new MemberId(Typed::string($document['member'])),
            'provider' => Provider::from(Typed::string($document['provider'])),
            'codeVerifier' => Typed::string($document['codeVerifier']),
            'expiresAt' => Documents::momentOf(Typed::string($document['expiresAt'])),
            'scopes' => array_map(Scope::from(...), Typed::strings($document['scopes'] ?? self::THE_CALENDAR)),
        ]);
    }

    public function forget(PendingAuthorization $authorization): void
    {
        $this->connectorsTable->transact([$this->connectorsTable->deleting(ConnectorsKeys::authorization($authorization->state), ConnectorsKeys::AUTHORIZATION)]);
    }
}
