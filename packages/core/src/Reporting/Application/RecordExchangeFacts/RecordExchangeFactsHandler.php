<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordExchangeFacts;

use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Counts the exchanges of an account, and those of each month, from the facts Prospecting publishes (ADR 0099).
 */
final readonly class RecordExchangeFactsHandler implements EventHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(ExchangeFactsChanged $event): void
    {
        $this->recorder->record(new ExchangeFact(new AccountId($event->accountId), new ExchangeId($event->exchangeId), $event->revision, $event->present, $event->notedAt));
    }
}
