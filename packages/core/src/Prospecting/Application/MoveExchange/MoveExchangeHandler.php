<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\MoveExchange;

use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class MoveExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(MoveExchange $command): void
    {
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $duration = null === $command->durationInMinutes ? null : Duration::inMinutes($command->durationInMinutes);
        $exchange->moveTo($command->plannedAt, $duration, by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
