<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PushBackErasureOfContactToAttach;

use Prospecter\Core\Prospecting\Domain\Event\SetsTheMomentOfAnExchange;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;

/**
 * A meeting with a contact to attach later than their last one pushes their erasure back (ADR 0146); nothing for an
 * exchange kept for an organization, gone meanwhile, or whose contact is attached or erased already.
 */
final readonly class PushBackErasureOfContactToAttachHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(private ExchangeRepository $exchanges, private ContactToAttachRepository $contactsToAttach, private Events $events)
    {
    }

    public function __invoke(SetsTheMomentOfAnExchange $event): void
    {
        $exchange = $this->exchangeOf($event);
        $contact = $exchange?->contactToAttach();
        $contactToAttach = null === $contact ? null : $this->contactsToAttach->find($contact);

        if (null !== $exchange && null !== $contactToAttach) {
            $contactToAttach->meetAgainAt($exchange->happensAt(), at: $event->occurredAt);
            $this->contactsToAttach->save($contactToAttach);
            $this->publishWhatHappenedTo($contactToAttach);
        }
    }

    private function exchangeOf(SetsTheMomentOfAnExchange $event): ?Exchange
    {
        try {
            return $this->exchanges->get($event->subject());
        } catch (UnknownExchange) {
            return null;
        }
    }
}
