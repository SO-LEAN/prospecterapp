<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Service;

use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Port\OrganizationsOfContacts;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The contacts of the account a contact resembles, by their names, in one of the organizations where they work: a
 * rule on the whole base, out of reach of one person alone.
 */
final readonly class DuplicateSuspicion
{
    public function __construct(
        private PersonRepository $persons,
        private OrganizationsOfContacts $organizations,
    ) {
    }

    /**
     * @param list<OrganizationId> $itsOrganizations where the contact works
     *
     * @return list<PersonId>
     */
    #[\NoDiscard]
    public function resemblingTo(Person $contact, array $itsOrganizations): array
    {
        $resembling = $this->resemblingAnywhere($contact);

        return [] === $resembling ? [] : self::workingIn($itsOrganizations, $resembling, $this->organizations->organizationsOf($resembling, $contact->accountId()));
    }

    /**
     * Where the contact works asked with where those resembling them do.
     *
     * @return list<PersonId>
     */
    #[\NoDiscard]
    public function resemblingWhereTheyWork(Person $contact): array
    {
        $resembling = $this->resemblingAnywhere($contact);

        if ([] === $resembling) {
            return [];
        }

        $organizations = $this->organizations->organizationsOf([$contact->id(), ...$resembling], $contact->accountId());

        return self::workingIn($organizations[$contact->id()->value] ?? [], $resembling, $organizations);
    }

    /**
     * @return list<PersonId>
     */
    private function resemblingAnywhere(Person $contact): array
    {
        $key = $contact->resemblanceKey();

        return null === $key ? [] : $this->persons->resembling($contact->accountId(), $key, except: $contact->id());
    }

    /**
     * @param list<OrganizationId>                $organizations
     * @param list<PersonId>                      $contacts
     * @param array<string, list<OrganizationId>> $theirOrganizations by person id
     *
     * @return list<PersonId> those of the contacts working in one of the organizations
     */
    private static function workingIn(array $organizations, array $contacts, array $theirOrganizations): array
    {
        return array_values(array_filter($contacts, static fn (PersonId $contact): bool => array_any(
            $theirOrganizations[$contact->value] ?? [],
            static fn (OrganizationId $theirs): bool => array_any($organizations, $theirs->equals(...)),
        )));
    }
}
