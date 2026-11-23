<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AddContact;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Port\KnownContacts;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class AddContactHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(private OrganizationRepository $organizations, private KnownContacts $knownContacts, private Events $events)
    {
    }

    public function __invoke(AddContact $command): void
    {
        $contact = new ContactId($command->personId);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $this->ensureKnownBy($contact, $organization);
        $organization->addContact($contact, by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->organizations->save($organization);
        $this->publishWhatHappenedTo($organization);
    }

    private function ensureKnownBy(ContactId $contact, Organization $organization): void
    {
        if (!$this->knownContacts->isAContactOf($contact, $organization->accountId())) {
            throw UnknownContact::identifiedBy($contact);
        }
    }
}
