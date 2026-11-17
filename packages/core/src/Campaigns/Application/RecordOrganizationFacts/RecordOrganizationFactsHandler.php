<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RecordOrganizationFacts;

use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Keeps the contacts an organization names, who inherit its tags (ADR 0141), and where it is (ADR 0142). A fact
 * published before its contacts were tells none: the rebuild reads them again; one published before its municipality
 * was tells it unknown until the next.
 */
final readonly class RecordOrganizationFactsHandler implements EventHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(OrganizationFactsChanged $event): void
    {
        if (null === $event->contacts) {
            return;
        }

        $this->recorder->record(new OrganizationFact(
            new AccountId($event->accountId),
            new OrganizationId($event->organizationId),
            $event->revision,
            $event->present,
            array_map(static fn (string $contact): PersonId => new PersonId($contact), $event->contacts),
            new Locality($event->municipalityCode, $event->country),
        ));
    }
}
