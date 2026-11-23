<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepExchangesOfForgottenContact;

use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * An erased contact's exchanges stay in their organizations' timelines, because they are the account's commercial
 * history. Their text and their files must be redacted (ADR 0079). The member who erased the contact is the change's
 * author.
 */
final readonly class KeepExchangesOfForgottenContactHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $author = new MemberId($event->memberId);

        foreach ($this->exchanges->withContact(new ContactId($event->personId), new AccountId($event->accountId)) as $exchange) {
            $exchange->forgetItsContact(by: $author, at: $event->occurredAt);
            $this->exchanges->save($exchange);
            $this->publishWhatHappenedTo($exchange);
        }
    }
}
