<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepExchangesOfAttachedContact;

use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * The exchanges their holder kept while the contact waited to be attached follow them into the organization they
 * join (ADR 0146); those kept for organizations already stay as they are (ADR 0074).
 */
final readonly class KeepExchangesOfAttachedContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private ExchangeRepository $exchanges, private Events $events)
    {
    }

    public function __invoke(ContactAdded $event): void
    {
        foreach ($this->exchanges->withContact($event->contact, $event->accountId) as $exchange) {
            if (null !== $exchange->contactToAttach()) {
                $exchange->keepFor($event->organization, by: $event->by, at: $event->occurredAt);
                $this->exchanges->save($exchange);
                $this->publishWhatHappenedTo($exchange);
            }
        }
    }
}
