<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Service;

use Prospecter\Core\Prospecting\Domain\Exception\ContactToAttachOfAnother;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Model\ContactToAttach;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Repository\ContactToAttachRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The organizations of the account of a prospector a person is a contact of: a rule on the whole prospecting
 * base, out of reach of one organization. A person none of them names is no contact of the account, unless they
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
    public function of(PersonId $contact, AccountId $account): array
    {
        $ids = $this->idsNaming($contact, $account);

        return [] === $ids ? throw UnknownContact::identifiedBy($contact) : $ids;
    }

    /**
     * Those what a prospector notes or plans with the contact is kept for: none while the contact waits for the
     * prospector to attach them, their holder keeping it until then.
     *
     * @return list<OrganizationId>
     *
     * @throws UnknownContact|ContactToAttachOfAnother
     */
    public function keepersOf(PersonId $contact, Prospector $prospector): array
    {
        $ids = $this->idsNaming($contact, $prospector->accountId);

        if ([] === $ids) {
            $this->ensureHeldBy($this->toAttach($contact, $prospector->accountId) ?? throw UnknownContact::identifiedBy($contact), $prospector);
        }

        return $ids;
    }

    /**
     * What the timeline of a person offers a prospector: nothing for a person who is no contact of the account; only
     * the reading for a contact waiting for another member to attach them, met by their holder only (ADR 0146).
     *
     * @return list<TimelineIntent>
     */
    public function timelineIntentsOf(PersonId $contact, Prospector $prospector): array
    {
        if (!$this->isAContactOfTheAccount($contact, $prospector->accountId)) {
            return [];
        }

        return $this->isToAttachByAnother($contact, $prospector) ? [TimelineIntent::ConsultTimeline] : TimelineIntent::cases();
    }

    private function isToAttachByAnother(PersonId $contact, Prospector $prospector): bool
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
            throw ContactToAttachOfAnother::because('A contact to attach is dealt with by the member they met until attached');
        }
    }

    /**
     * @throws UnknownContact
     */
    public function ensureIsAContactOfTheAccount(PersonId $contact, AccountId $account): void
    {
        if (!$this->isAContactOfTheAccount($contact, $account)) {
            throw UnknownContact::identifiedBy($contact);
        }
    }

    public function isAContactOfTheAccount(PersonId $person, AccountId $account): bool
    {
        return [] !== $this->naming($person, $account) || null !== $this->toAttach($person, $account);
    }

    /**
     * @return list<OrganizationId>
     */
    private function idsNaming(PersonId $contact, AccountId $account): array
    {
        return array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $this->naming($contact, $account));
    }

    /**
     * @return list<Organization>
     */
    private function naming(PersonId $contact, AccountId $account): array
    {
        return $this->organizations->withContacts([$contact], $account);
    }

    private function toAttach(PersonId $person, AccountId $account): ?ContactToAttach
    {
        $contactToAttach = $this->contactsToAttach->find($person);

        return $contactToAttach?->accountId()->equals($account) ? $contactToAttach : null;
    }
}
