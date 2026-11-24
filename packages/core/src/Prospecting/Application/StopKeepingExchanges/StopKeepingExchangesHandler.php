<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StopKeepingExchanges;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An organization that is no longer prospected takes its exchanges along. Those still kept for another organization
 * are saved. Each of the others is erased by its own command (EraseExchangeLeftWithoutOrganization, ADR 0074),
 * because one transaction holds a bounded number of writes.
 */
final readonly class StopKeepingExchangesHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(StopKeepingExchanges $command): void
    {
        $organization = new OrganizationId($command->organizationId);

        foreach ($this->exchanges->keptFor($organization) as $exchange) {
            $exchange->stopKeepingFor($organization, by: new MemberId($command->memberId), at: $command->executedAt);

            if ($exchange->isKeptForAnOrganization()) {
                $this->exchanges->save($exchange);
            }

            $this->publishWhatHappenedTo($exchange);
        }
    }
}
