<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchange;

use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class CorrectExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private Events $events,
    ) {
    }

    public function __invoke(CorrectExchange $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->correct(
            new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text, $command->missed),
            by: $prospector,
            at: $command->executedAt,
        );
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
