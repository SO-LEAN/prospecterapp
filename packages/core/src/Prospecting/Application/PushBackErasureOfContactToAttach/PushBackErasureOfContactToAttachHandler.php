<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PushBackErasureOfContactToAttach;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * A meeting with a contact to attach that is later than their last one pushes their erasure back (ADR 0146). Nothing
 * happens for an exchange kept for an organization, for an exchange gone meanwhile, or for one whose contact is
 * already attached or erased.
 */
final readonly class PushBackErasureOfContactToAttachHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private ExchangeRepository $exchanges, private ContactToAttachRepository $contactsToAttach, private Events $events)
    {
    }

    public function __invoke(PushBackErasureOfContactToAttach $command): void
    {
        $exchange = $this->exchangeOf(new ExchangeId($command->exchangeId));
        $contact = $exchange?->contactToAttach();
        $contactToAttach = null === $contact ? null : $this->contactsToAttach->find($contact);

        if (null !== $exchange && null !== $contactToAttach) {
            $contactToAttach->meetAgainAt($exchange->happensAt(), at: $command->executedAt);
            $this->contactsToAttach->save($contactToAttach);
            $this->publishWhatHappenedTo($contactToAttach);
        }
    }

    private function exchangeOf(ExchangeId $id): ?Exchange
    {
        try {
            return $this->exchanges->get($id);
        } catch (UnknownExchange) {
            return null;
        }
    }
}
