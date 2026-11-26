<?php

declare(strict_types=1);

namespace App\Prospecting\AntiCorruption;

use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\DescribePersons;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\DescribePersonsService;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\KeptPersonDescriptions;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\LinkResponse;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\PersonDescriptionResponse;
use Prospecter\Core\Prospecting\Application\PersonDescription;
use Prospecter\Core\Prospecting\Application\PersonsShown;
use Prospecter\Core\Prospecting\Application\Port\PersonDescriptionRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Application\Response\ExternalLink;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0217). Accounts publishes each member's person,
 * and PersonalData describes the persons as the reader reads them, each context in one call.
 */
final readonly class AccountsAndPersonalDataPersonDescriptions implements PersonDescriptionRetriever
{
    public function __construct(
        private ConsultMembersService $consultMembersService,
        private DescribePersonsService $describePersonsService,
    ) {
    }

    /**
     * @param list<ContactId> $contacts
     * @param list<MemberId>  $members
     */
    public function shownTo(MemberId $reader, array $contacts, array $members): PersonsShown
    {
        $personOfMember = $this->personsOfMembers($members);
        $described = $this->described($reader, [...array_map(static fn (ContactId $contact): string => $contact->value, $contacts), ...array_values($personOfMember)]);

        return new PersonsShown(
            array_intersect_key($described, array_flip(array_map(static fn (ContactId $contact): string => $contact->value, $contacts))),
            array_filter(array_map(static fn (string $personId): ?PersonDescription => $described[$personId] ?? null, $personOfMember)),
        );
    }

    /**
     * @param list<MemberId> $members
     *
     * @return array<string, string> the person id of each member found, by member id
     */
    private function personsOfMembers(array $members): array
    {
        if ([] === $members) {
            return [];
        }

        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers(array_values(array_unique(array_map(static fn (MemberId $member): string => $member->value, $members)))), $kept);

        return array_map(static fn (MemberResponse $member): string => $member->personId, $kept->members);
    }

    /**
     * @param list<string> $personIds
     *
     * @return array<string, PersonDescription> by person id
     */
    private function described(MemberId $reader, array $personIds): array
    {
        if ([] === $personIds) {
            return [];
        }

        $kept = new KeptPersonDescriptions();
        ($this->describePersonsService)(new DescribePersons($reader->value, array_values(array_unique($personIds))), $kept);

        return array_column(array_map(self::descriptionOf(...), $kept->persons), null, 'personId');
    }

    private static function descriptionOf(PersonDescriptionResponse $person): PersonDescription
    {
        return new PersonDescription(
            personId: $person->personId,
            masked: $person->masked,
            firstName: $person->firstName,
            lastName: $person->lastName,
            title: $person->title,
            jobTitle: $person->jobTitle,
            email: $person->email,
            phoneNumber: $person->phoneNumber,
            language: $person->language,
            links: array_map(static fn (LinkResponse $link): ExternalLink => new ExternalLink($link->source, $link->identifier, $link->address), $person->links),
        );
    }
}
