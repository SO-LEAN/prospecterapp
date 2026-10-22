<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseExchange;

use Prospecter\Core\Prospecting\Application\AttachedFiles;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class EraseExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private Events $events,
        private AttachedFiles $attachedFiles,
    ) {
    }

    public function __invoke(EraseExchange $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->erase(by: $prospector, at: $command->executedAt);
        $this->exchanges->remove($exchange);
        $this->attachedFiles->forgetThoseOf($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
