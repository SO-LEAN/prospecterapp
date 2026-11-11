<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspecting;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\RebuildInterrupted;

/**
 * The figures Reporting keeps from the facts of the other contexts (ADR 0103): the facts it receives, its rebuilds,
 * and the figures a member consults.
 */
final class ReportingContext implements Context
{
    use SharesTheScenario;

    private const array FIGURES = [
        'organisations prospectées' => 'activeOrganizations',
        'organisations géolocalisées' => 'locatedOrganizations',
        'contacts' => 'contacts',
        'échanges' => 'exchanges',
        'échanges du mois' => 'exchangesThisMonth',
    ];

    #[Given('les chiffres ont été recalculés')]
    #[When('les chiffres sont recalculés')]
    public function theFiguresAreRebuilt(): void
    {
        $this->application->dispatch(new RebuildFigures());
        Expect::that(null === $this->application->refusal(), sprintf('The figures were expected to be rebuilt, got: %s', $this->application->refusal()?->getMessage()));
    }

    #[When('les chiffres sont recalculés, et qu\'avant qu\'il ne s\'achève je commence à prospecter l\'organisation :')]
    public function theFiguresAreRebuiltWhileIStartProspecting(TableNode $table): void
    {
        $this->application->projectionVersions->beforeTheNextActivation(fn () => $this->startProspecting($table));
        $this->theFiguresAreRebuilt();
    }

    #[Given('un recalcul interrompu, sa version encore en construction')]
    public function aRebuildWasInterrupted(): void
    {
        $this->application->projectionVersions->interruptTheNextActivation();

        try {
            $this->application->dispatch(new RebuildFigures());
        } catch (RebuildInterrupted) {
            return;
        }

        throw new \LogicException('The rebuild was expected to be interrupted');
    }

    #[When('les chiffres sont recalculés, et que pendant qu\'il vide les versions abandonnées je commence à prospecter l\'organisation :')]
    public function theFiguresAreRebuiltWhileIStartProspectingDuringTheRemoval(TableNode $table): void
    {
        $this->application->projectionVersions->whileRemovingTheAbandoned(fn () => $this->startProspecting($table));
        $this->theFiguresAreRebuilt();
    }

    #[Then('seule la nouvelle version active garde des faits et des chiffres')]
    public function onlyTheActiveVersionKeepsFactsAndFigures(): void
    {
        $versions = $this->application->projectionVersions;

        Expect::same([$versions->active()], $versions->versionsKeepingFactsOrFigures());
    }

    #[Given('/^Reporting (?:a reçu|reçoit) le fait de "(?<name>[^"]+)" : (?<presence>présente|absente), (?<location>géolocalisée|non géolocalisée), révision (?<revision>\d+)$/')]
    public function reportingReceivesTheFactsOf(string $name, string $presence, string $location, int $revision): void
    {
        $this->application->publishAgain($this->factsOf($name, $presence, $location, $revision));
    }

    #[When('Reporting reçoit encore le dernier fait publié de :name')]
    public function reportingReceivesTheLastFactsAgain(string $name): void
    {
        $organizationId = $this->scenario->idOf($name);
        $last = array_last(array_filter($this->application->events->published(OrganizationFactsChanged::class), static fn (OrganizationFactsChanged $facts): bool => $facts->organizationId === $organizationId));

        Expect::that(null !== $last, sprintf('No facts of "%s" were published', $name));
        $this->application->publishAgain($last);
    }

    #[When('Reporting reçoit en même temps les faits :')]
    public function reportingReceivesFactsAtTheSameTime(TableNode $table): void
    {
        $this->application->publishAtTheSameTime(...array_map(
            fn (array $row): OrganizationFactsChanged => $this->factsOf($row['organisation'], $row['présence'], $row['géolocalisation'], (int) $row['révision']),
            $table->getColumnsHash(),
        ));
    }

    #[Then('mes chiffres sont :')]
    public function myFiguresAre(TableNode $table): void
    {
        $figures = $this->application->consultFigures(new ConsultFigures($this->application->signedIn(), $this->application->now()));
        Expect::that(null !== $figures, sprintf('The figures were expected, got: %s', $this->application->refusal()?->getMessage()));
        $expected = array_map(intval(...), $table->getRowsHash());

        Expect::same($expected, array_map(static fn (string $label): int => $figures->{self::FIGURES[$label]}, array_combine(array_keys($expected), array_keys($expected))));
    }

    private function factsOf(string $name, string $presence, string $location, int $revision): OrganizationFactsChanged
    {
        return new OrganizationFactsChanged($this->scenario->meAsProspector()->accountId->value, $this->scenario->idOf($name), $revision, 'présente' === $presence, 'géolocalisée' === $location, $this->application->now());
    }

    private function startProspecting(TableNode $table): void
    {
        $values = Vocabulary::values($table->getRowsHash());
        $id = $this->application->newIdentity();
        $this->application->dispatch(new StartProspecting(
            prospectorId: $this->application->signedIn(),
            id: $id,
            corporateName: $values['raison sociale'],
            form: null,
            type: null,
            email: null,
            phoneNumber: null,
            language: null,
            street: null,
            postalCode: null,
            city: $values['ville'],
            country: null,
            observations: null,
            holdingId: null,
            executedAt: $this->application->now(),
        ));
        $this->scenario->remember($values['raison sociale'], $id);
    }
}
