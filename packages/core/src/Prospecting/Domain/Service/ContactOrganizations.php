<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\ContactToAttachOfAnother;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * The organizations of a prospector's account that name a person as a contact. The rule covers the whole prospecting
 * base, out of one organization's reach. A person whom none of them names is no contact of the account, unless they
 * wait to be attached (ADR 0146).
 */
final readonly class ContactOrganizations
{
    public function __construct(private OrganizationRepository $organizations, private ContactToAttachRepository $contactsToAttach)
    {
    }

    /**
     * @return non-empty-list<OrganizationId>
     *
     * @throws UnknownContact
     */
    public function of(ContactId $contact, AccountId $account): array
    {
        $ids = $this->idsNaming($contact, $account);

        return [] === $ids ? throw UnknownContact::identifiedBy($contact) : $ids;
    }

    /**
     * The organizations that keep what a prospector logs or plans with the contact. There are none while the contact
     * waits for the prospector to attach them. Until then, their holder keeps it.
     *
     * @return list<OrganizationId>
     *
     * @throws UnknownContact|ContactToAttachOfAnother
     */
    public function keepersOf(ContactId $contact, Prospector $prospector): array
    {
        $ids = $this->idsNaming($contact, $prospector->accountId);

        if ([] === $ids) {
            $this->ensureHeldBy($this->toAttach($contact, $prospector->accountId) ?? throw UnknownContact::identifiedBy($contact), $prospector);
        }

        return $ids;
    }

    /**
     * What a person's timeline offers a prospector. It offers nothing for a person who is no contact of the account. It
     * offers only the reading for a contact who waits for another member to attach them, because only their holder
     * meets them (ADR 0146).
     *
     * @return list<TimelineIntent>
     */
    public function timelineIntentsOf(ContactId $contact, Prospector $prospector): array
    {
        if (!$this->isAContactOfTheAccount($contact, $prospector->accountId)) {
            return [];
        }

        return $this->isToAttachByAnother($contact, $prospector) ? [TimelineIntent::ConsultTimeline] : TimelineIntent::cases();
    }

    private function isToAttachByAnother(ContactId $contact, Prospector $prospector): bool
    {
        $contactToAttach = $this->toAttach($contact, $prospector->accountId);

        return null !== $contactToAttach && !$contactToAttach->isHeldBy($prospector->id);
    }

    /**
     * @throws ContactToAttachOfAnother
     */
    private function ensureHeldBy(ContactToAttach $contactToAttach, Prospector $prospector): void
    {
        if (!$contactToAttach->isHeldBy($prospector->id)) {
            throw ContactToAttachOfAnother::because('Until a contact is attached, only the member who met them can deal with them');
        }
    }

    /**
     * @throws UnknownContact
     */
    public function ensureIsAContactOfTheAccount(ContactId $contact, AccountId $account): void
    {
        if (!$this->isAContactOfTheAccount($contact, $account)) {
            throw UnknownContact::identifiedBy($contact);
        }
    }

    public function isAContactOfTheAccount(ContactId $person, AccountId $account): bool
    {
        return [] !== $this->naming($person, $account) || null !== $this->toAttach($person, $account);
    }

    /**
     * @return list<OrganizationId>
     */
    private function idsNaming(ContactId $contact, AccountId $account): array
    {
        return array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $this->naming($contact, $account));
    }

    /**
     * @return list<Organization>
     */
    private function naming(ContactId $contact, AccountId $account): array
    {
        return $this->organizations->withContacts([$contact], $account);
    }

    private function toAttach(ContactId $person, AccountId $account): ?ContactToAttach
    {
        $contactToAttach = $this->contactsToAttach->find($person);

        return $contactToAttach?->accountId()->equals($account) ? $contactToAttach : null;
    }
}
