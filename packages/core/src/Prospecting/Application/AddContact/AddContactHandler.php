<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AddContact;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\KnownContacts;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\Prospectors;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class AddContactHandler implements CommandHandler
{
    public function __construct(private OrganizationRepository $organizations, private Prospectors $prospectors, private KnownContacts $knownContacts)
    {
    }

    public function __invoke(AddContact $command): void
    {
        $prospector = $this->prospectors->get(new MemberId($command->prospectorId));
        $contact = new PersonId($command->personId);
        $this->ensureKnown($contact, $prospector);
        $organization = $this->organizations->get(new OrganizationId($command->id));
        $organization->addContact($contact, by: $prospector, at: $command->executedAt);
        $this->organizations->save($organization);
    }

    private function ensureKnown(PersonId $contact, Prospector $prospector): void
    {
        if (!$this->knownContacts->isAContactOf($contact, $prospector->accountId)) {
            throw UnknownContact::identifiedBy($contact);
        }
    }
}
