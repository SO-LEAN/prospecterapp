<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MarkExchangeDone;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class MarkExchangeDoneHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(MarkExchangeDone $command): void
    {
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->markDone($command->occurredAt, $command->report, by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
