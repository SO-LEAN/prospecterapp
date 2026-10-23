<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepExchangesOfForgottenContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchanges of an erased contact stay in the timelines of their organizations, the commercial history of
 * the account, their text and their files to make conform (ADR 0079), by the member who erased it.
 */
final readonly class KeepExchangesOfForgottenContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private Events $events,
    ) {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($event->memberId));

        foreach ($this->exchanges->withContact(new PersonId($event->personId), new AccountId($event->accountId)) as $exchange) {
            $exchange->forgetItsContact(by: $prospector, at: $event->occurredAt);
            $this->exchanges->save($exchange);
            $this->publishWhatHappenedTo($exchange);
        }
    }
}
