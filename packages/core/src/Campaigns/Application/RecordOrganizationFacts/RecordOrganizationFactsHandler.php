<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\RecordOrganizationFacts;

use Prospecter\Core\Campaigns\Application\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Keeps the contacts that an organization names, who inherit its tags (ADR 0141), and where the organization is (ADR
 * 0142). A fact published before the facts carried the contacts tells none of them. The rebuild reads them again. A
 * fact published before the facts carried the municipality leaves it unknown until the next fact.
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
            array_map(static fn (string $contact): ContactId => new ContactId($contact), $event->contacts),
            new Locality($event->municipalityCode, $event->country),
        ));
    }
}
