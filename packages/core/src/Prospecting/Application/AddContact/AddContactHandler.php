<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AddContact;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\KnownContacts;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class AddContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private KnownContacts $knownContacts, private Events $events)
    {
    }

    public function __invoke(AddContact $command): void
    {
        $contact = new PersonId($command->personId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $this->ensureKnownBy($contact, $organization);
        $organization->addContact($contact, by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }

    private function ensureKnownBy(PersonId $contact, Organization $organization): void
    {
        if (!$this->knownContacts->isAContactOf($contact, $organization->accountId())) {
            throw UnknownContact::identifiedBy($contact);
        }
    }
}
