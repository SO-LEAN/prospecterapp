<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ConsultExchangeSync;

use Prospecter\Core\Connectors\Application\ExchangeSync;
use Prospecter\Core\Connectors\Application\Port\ExchangeSyncRetriever;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Where my exchange's push into my calendar stands (ADR 0133), while I have a connection there. The state is the one
 * that the exchange's entry keeps. An exchange that I planned, still to come and never pushed, is pending. There is
 * no state for another member's exchange, nor for a past exchange that was never pushed.
 */
final readonly class ConsultExchangeSyncHandler implements QueryHandler
{
    private const Provider PROVIDER = Provider::Google;

    public function __construct(
        private HeldExchanges $exchanges,
        private ExchangeSyncRetriever $exchangeSyncRetriever,
        private ConnectionRepository $connections,
        private Members $members,
    ) {
    }

    public function __invoke(ConsultExchangeSync $query, ConsultExchangeSyncOutput $output): void
    {
        $me = new MemberId($query->memberId);
        $connection = $this->connections->find($me, self::PROVIDER);

        $output->present(null === $connection ? null : $this->syncOf(new ExchangeId($query->exchangeId), $me, $connection, $query->readAt));
    }

    private function syncOf(ExchangeId $id, MemberId $me, Connection $connection, \DateTimeImmutable $at): ?ExchangeSync
    {
        $kept = $this->exchangeSyncRetriever->find($id, $me);
        if (null !== $kept) {
            return $kept->withMyConnection($connection->isConnected());
        }

        $exchange = $this->exchanges->find($this->members->get($me)->accountId, $id);

        return null !== $exchange && $exchange->isHeldBy($me) && $exchange->awaitsItsPushAt($at)
            ? ExchangeSync::pending(self::PROVIDER->value, null, $connection->isConnected())
            : null;
    }
}
