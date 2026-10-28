<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchangeText;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class CorrectExchangeTextHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(CorrectExchangeText $command): void
    {
        $author = new MemberId($command->prospectorId);
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->correctText($command->text, by: $author, at: $command->executedAt);

        if ($command->madeConform) {
            $exchange->declareConform(by: $author, at: $command->executedAt);
        }

        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
