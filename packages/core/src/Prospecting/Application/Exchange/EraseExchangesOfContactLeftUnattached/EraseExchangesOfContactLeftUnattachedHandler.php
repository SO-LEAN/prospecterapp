<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\EraseExchangesOfContactLeftUnattached;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Erases the exchanges of a contact left unattached. They were kept for no organization, so nothing needs them and
 * nothing is left to redact. They go with the contact (ADR 0146). The policy of their erasure removes their files.
 */
final readonly class EraseExchangesOfContactLeftUnattachedHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private ExchangeRepository $exchanges, private Events $events)
    {
    }

    public function __invoke(EraseExchangesOfContactLeftUnattached $command): void
    {
        foreach ($this->exchanges->withContact(new ContactId($command->contactId), new AccountId($command->accountId)) as $exchange) {
            $this->eraseKeptForNone($exchange, $command);
        }
    }

    private function eraseKeptForNone(Exchange $exchange, EraseExchangesOfContactLeftUnattached $command): void
    {
        if ($exchange->isKeptForAnOrganization()) {
            return;
        }

        $exchange->erase(by: new MemberId($command->holderId), at: $command->executedAt);
        $this->exchanges->remove($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
