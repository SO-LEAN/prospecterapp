<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchange;

use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class CorrectExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(CorrectExchange $command): void
    {
        $author = new MemberId($command->prospectorId);
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->correct(
            new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text, $command->missed),
            by: $author,
            at: $command->executedAt,
        );

        if ($command->madeConform) {
            $exchange->declareConform(by: $author, at: $command->executedAt);
        }

        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
