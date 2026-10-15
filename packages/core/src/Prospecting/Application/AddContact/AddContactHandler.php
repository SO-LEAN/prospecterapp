<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AddContact;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\KnownContacts;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class AddContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private ProspectorService $prospectorService, private KnownContacts $knownContacts, private Events $events)
    {
    }

    public function __invoke(AddContact $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $contact = new PersonId($command->personId);
        $this->ensureKnown($contact, $prospector);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->addContact($contact, by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }

    private function ensureKnown(PersonId $contact, Prospector $prospector): void
    {
        if (!$this->knownContacts->isAContactOf($contact, $prospector->accountId)) {
            throw UnknownContact::identifiedBy($contact);
        }
    }
}
