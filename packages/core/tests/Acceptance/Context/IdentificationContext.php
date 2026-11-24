<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons\Candidate as PersonCandidate;
use Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons\Identification as PersonIdentification;
use Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons\IdentifyPersons;
use Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons\PersonClues;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Identification;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\IdentifyOrganizations;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\OrganizationClues;
use Tests\Core\Acceptance\Expect;

/**
 * The AI gives organizations and persons as it sees them. Each one is known, new, or to confirm among candidates.
 */
final class IdentificationContext implements Context
{
    use SharesTheScenario;

    private const array STATUSES = [Identification::KNOWN => 'connue', Identification::NEW => 'nouvelle', Identification::TO_CONFIRM => 'à confirmer'];
    private const string LIST_SEPARATOR = ', ';

    /** @var list<array{statut: string, candidates: string}>|null */
    private ?array $identified = null;

    #[When('j\'identifie les organisations :')]
    public function iIdentifyOrganizations(TableNode $table): void
    {
        $clues = array_map(self::organizationClues(...), $table->getColumnsHash());
        $identifications = $this->application->identifyOrganizations(new IdentifyOrganizations($this->application->signedIn(), $clues));
        $this->identified = null === $identifications ? null : array_map(self::described(...), $identifications);
    }

    #[When('j\'identifie les personnes :')]
    public function iIdentifyPersons(TableNode $table): void
    {
        $clues = array_map($this->personClues(...), $table->getColumnsHash());
        $identifications = $this->application->identifyPersons(new IdentifyPersons($this->application->signedIn(), $clues));
        $this->identified = null === $identifications ? null : array_map(self::described(...), $identifications);
    }

    #[Then('les organisations sont identifiées :')]
    #[Then('les personnes sont identifiées :')]
    public function theyAreIdentified(TableNode $table): void
    {
        Expect::same($table->getColumnsHash(), $this->identified);
    }

    /**
     * @param array<string, string> $row
     */
    private static function organizationClues(array $row): OrganizationClues
    {
        return new OrganizationClues(
            name: self::orNone($row['nom'] ?? ''),
            siret: self::orNone($row['SIRET'] ?? ''),
            links: self::listOf($row['liens'] ?? ''),
            website: self::orNone($row['site web'] ?? ''),
            emails: self::listOf($row['e-mails'] ?? ''),
            city: self::orNone($row['ville'] ?? ''),
        );
    }

    /**
     * @param array<string, string> $row
     */
    private function personClues(array $row): PersonClues
    {
        return new PersonClues(
            firstName: self::orNone($row['prénom'] ?? ''),
            lastName: self::orNone($row['nom'] ?? ''),
            links: self::listOf($row['liens'] ?? ''),
            emails: self::listOf($row['e-mails'] ?? ''),
            phoneNumbers: self::listOf($row['téléphones'] ?? ''),
            organizationIds: array_map(fn (string $name): string => $this->scenario->organizationNamed($name)->id()->value, self::listOf($row['organisations'] ?? '')),
        );
    }

    private static function orNone(string $value): ?string
    {
        return '' === $value ? null : $value;
    }

    /**
     * @return list<string>
     */
    private static function listOf(string $values): array
    {
        return '' === $values ? [] : explode(self::LIST_SEPARATOR, $values);
    }

    /**
     * @return array{statut: string, candidates: string}
     */
    private static function described(Identification|PersonIdentification $identification): array
    {
        return [
            'statut' => self::STATUSES[$identification->status],
            'candidates' => implode(self::LIST_SEPARATOR, array_map(static fn (Candidate|PersonCandidate $candidate): string => $candidate->name, $identification->candidates)),
        ];
    }
}
