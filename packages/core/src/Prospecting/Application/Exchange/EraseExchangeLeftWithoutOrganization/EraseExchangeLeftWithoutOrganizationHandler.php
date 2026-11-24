<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\EraseExchangeLeftWithoutOrganization;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Erases an exchange kept for no organization, with the personal data its text and its files may hold
 * (ADR 0074, 0077). The member who stopped prospecting its organization is the author. When the command comes again
 * after the erasure, it has nothing left to do.
 */
final readonly class EraseExchangeLeftWithoutOrganizationHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Events $events,
    ) {
    }

    public function __invoke(EraseExchangeLeftWithoutOrganization $command): void
    {
        try {
            $exchange = $this->exchanges->get(new ExchangeId($command->exchangeId));
        } catch (UnknownExchange) {
            return;
        }

        $exchange->eraseWithItsOrganization(by: new MemberId($command->memberId), at: $command->executedAt);
        $this->exchanges->remove($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
