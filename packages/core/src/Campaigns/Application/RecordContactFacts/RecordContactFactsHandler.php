<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RecordContactFacts;

use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Keeps how a contact is reached, which sets its automatic tags (ADR 0141).
 */
final readonly class RecordContactFactsHandler implements CommandHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(RecordContactFacts $command): void
    {
        $this->recorder->record(new ContactFact(
            new AccountId($command->accountId),
            new ContactId($command->contactId),
            $command->revision,
            $command->present,
            new Reach($command->hasEmail, $command->hasPhone, $command->hasMobile, $command->hasLinkedIn),
        ));
    }
}
