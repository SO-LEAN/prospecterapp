<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseExchange;

use Prospecter\Core\Prospecting\Application\AttachedFiles;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class EraseExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
        private AttachedFiles $attachedFiles,
    ) {
    }

    public function __invoke(EraseExchange $command): void
    {
        $author = new MemberId($command->prospectorId);
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->erase(by: $author, at: $command->executedAt);
        $this->exchanges->remove($exchange);
        $this->attachedFiles->forgetThoseOf($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
