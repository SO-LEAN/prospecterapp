<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordOrganizationFacts;

use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Counts the organizations of an account from the facts Prospecting publishes (ADR 0099).
 */
final readonly class RecordOrganizationFactsHandler implements EventHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(OrganizationFactsChanged $event): void
    {
        $this->recorder->record(new OrganizationFact(new AccountId($event->accountId), new OrganizationId($event->organizationId), $event->revision, $event->present, $event->located));
    }
}
