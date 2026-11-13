<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\CorrectExchangeVenue;

use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class CorrectExchangeVenueHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(CorrectExchangeVenue $command): void
    {
        $exchange = $this->exchanges->get(new ExchangeId($command->id));
        $exchange->correctVenue($command->place, VideoCallLink::orNone($command->link), by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
