<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\RecordOrganizationFacts;

use Prospecter\Core\Campaigns\Application\Tagging\FactRecorder;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Keeps the contacts that an organization names, who inherit its tags (ADR 0141), and where the organization is (ADR
 * 0142). A fact published before the facts carried the municipality leaves it unknown until the next fact.
 */
final readonly class RecordOrganizationFactsHandler implements CommandHandler
{
    public function __construct(private FactRecorder $recorder)
    {
    }

    public function __invoke(RecordOrganizationFacts $command): void
    {
        $this->recorder->record(new OrganizationFact(
            new AccountId($command->accountId),
            new OrganizationId($command->organizationId),
            $command->revision,
            $command->present,
            array_map(static fn (string $contact): ContactId => new ContactId($contact), $command->contacts),
            new Locality($command->municipalityCode, $command->country),
        ));
    }
}
