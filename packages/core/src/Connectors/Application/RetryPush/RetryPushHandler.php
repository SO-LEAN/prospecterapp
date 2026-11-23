<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\RetryPush;

use Prospecter\Core\Connectors\Application\CalendarPusher;
use Prospecter\Core\Connectors\Domain\Exception\NotTheHolder;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The holder pushes their exchange again, as the reaction to its changes does (ADR 0133). The exchange may have been
 * erased since, and its withdrawal refused. Then its entry tells that the exchange is the holder's.
 */
final readonly class RetryPushHandler implements CommandHandler
{
    public function __construct(
        private HeldExchanges $exchanges,
        private CalendarEntryRepository $entries,
        private Members $members,
        private CalendarPusher $pusher,
    ) {
    }

    public function __invoke(RetryPush $command): void
    {
        $me = $this->members->get(new MemberId($command->memberId));
        $id = new ExchangeId($command->exchangeId);
        $this->ensureHeldBy($me->id, $id, $me->accountId);
        $this->pusher->follow($id, $me->accountId, $command->executedAt);
    }

    private function ensureHeldBy(MemberId $me, ExchangeId $id, AccountId $accountId): void
    {
        $holder = $this->exchanges->find($accountId, $id)->holder ?? $this->entries->find($id)?->member();

        if (null === $holder || !$holder->equals($me)) {
            throw NotTheHolder::ofTheExchange();
        }
    }
}
