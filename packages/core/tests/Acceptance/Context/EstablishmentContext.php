<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\SearchEstablishments\FoundEstablishment;
use Prospecter\Core\Prospecting\Application\SearchEstablishments\SearchEstablishments;
use Prospecter\Core\Prospecting\Domain\Model\Establishment;
use Prospecter\Core\SharedKernel\Domain\Company\Siret;
use Tests\Core\Acceptance\Expect;

final class EstablishmentContext implements Context
{
    use SharesTheScenario;

    /** @var list<FoundEstablishment>|null */
    private ?array $proposed = null;
    private bool $unanswered = false;

    #[Given('l\'annuaire des entreprises connaît les établissements :')]
    public function theDirectoryKnows(TableNode $table): void
    {
        foreach ($table->getHash() as $row) {
            $this->application->directory->know(new Establishment(new Siret($row['SIRET']), $row['dénomination'], $row['adresse'], $row['activité'], 'fermé' === $row['état'], $row['commune']));
        }
    }

    #[Given('l\'annuaire des entreprises ne répond pas')]
    public function theDirectoryDoesNotAnswer(): void
    {
        $this->application->directory->stopAnswering();
    }

    #[When('je cherche le SIRET de :name')]
    public function iSearchTheSiretOf(string $name): void
    {
        try {
            $this->proposed = $this->application->searchEstablishments(new SearchEstablishments($this->scenario->idOf($name)));
        } catch (\RuntimeException) {
            $this->unanswered = true;
        }
    }

    #[Then('l\'annuaire me propose :')]
    public function theDirectoryProposes(TableNode $table): void
    {
        $proposed = Expect::present($this->proposed, sprintf('Establishments were expected, got: %s', $this->unanswered ? 'no answer' : $this->application->refusal()?->getMessage()));
        Expect::same($table->getHash(), array_map(static fn (FoundEstablishment $establishment): array => [
            'SIRET' => $establishment->siret,
            'dénomination' => $establishment->name,
            'adresse' => $establishment->address,
            'activité' => (string) $establishment->activity,
            'fermé' => $establishment->closed ? 'oui' : 'non',
        ], $proposed));
    }

    #[Then('l\'annuaire ne me propose rien')]
    public function theDirectoryProposesNothing(): void
    {
        Expect::same([], $this->proposed);
    }

    #[Then('l\'annuaire ne me propose rien, faute de réponse')]
    public function theDirectoryProposesNothingForWantOfAnAnswer(): void
    {
        Expect::that($this->unanswered, 'The directory was expected not to answer');
    }
}
