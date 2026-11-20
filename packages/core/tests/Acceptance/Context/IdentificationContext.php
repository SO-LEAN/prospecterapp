<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Identification;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\IdentifyOrganizations;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\OrganizationClues;
use Tests\Core\Acceptance\Expect;

/**
 * The AI gives organizations and persons as it sees them: each is known, new, or to confirm among candidates.
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

    #[Then('les organisations sont identifiées :')]
    public function theOrganizationsAreIdentified(TableNode $table): void
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
    private static function described(Identification $identification): array
    {
        return [
            'statut' => self::STATUSES[$identification->status],
            'candidates' => implode(self::LIST_SEPARATOR, array_map(static fn (Candidate $candidate): string => $candidate->name, $identification->candidates)),
        ];
    }
}
