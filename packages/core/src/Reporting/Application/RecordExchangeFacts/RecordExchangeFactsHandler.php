<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordExchangeFacts;

use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Counts an account's exchanges, in total and by month, from the facts that Prospecting publishes (ADR 0099).
 */
final readonly class RecordExchangeFactsHandler implements CommandHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(RecordExchangeFacts $command): void
    {
        $this->recorder->record(ExchangeFact::withStatus(new AccountId($command->accountId), new ExchangeId($command->exchangeId), $command->revision, $command->present, $command->status, $command->notedAt));
    }
}
