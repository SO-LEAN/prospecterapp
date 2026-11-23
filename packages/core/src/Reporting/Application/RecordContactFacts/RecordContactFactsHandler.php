<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordContactFacts;

use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\Reporting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Counts an account's contacts from the facts that PersonalData publishes (ADR 0099).
 */
final readonly class RecordContactFactsHandler implements CommandHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(RecordContactFacts $command): void
    {
        $this->recorder->record(new ContactFact(new AccountId($command->accountId), new ContactId($command->contactId), $command->revision, $command->present));
    }
}
