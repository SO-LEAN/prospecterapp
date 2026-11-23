<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The facts that Prospecting publishes each time an organization or an exchange changes (ADR 0046). The event is
 * the contract. The scenarios read the last event published about what they name.
 */
final class ProspectingFactsContext implements Context
{
    use SharesTheScenario;

    private const array STATUSES = ['planifié' => 'planned', 'fait' => 'done', 'manqué' => 'missed', 'annulé' => 'cancelled'];

    #[Then('/^le fait publié de "(?<name>[^"]+)" est (?<presence>présente|absente), (?<location>géolocalisée|non géolocalisée), révision (?<revision>\d+)$/')]
    public function theFactsOfTheOrganization(string $name, string $presence, string $location, int $revision): void
    {
        $organizationId = $this->scenario->idOf($name);
        $published = array_filter($this->application->events->published(OrganizationFactsChanged::class), static fn (OrganizationFactsChanged $facts): bool => $facts->organizationId === $organizationId);
        $last = array_last($published);

        $last = Expect::present($last, sprintf('No facts of "%s" were published', $name));
        Expect::same(
            ['present' => 'présente' === $presence, 'located' => 'géolocalisée' === $location, 'revision' => $revision, 'accountId' => $this->scenario->meAsProspector()->accountId->value],
            ['present' => $last->present, 'located' => $last->located, 'revision' => $last->revision, 'accountId' => $last->accountId],
        );
    }

    #[Then('aucun fait d\'organisation n\'est publié')]
    public function noFactsOfOrganizationArePublished(): void
    {
        Expect::same([], $this->application->events->published(OrganizationFactsChanged::class));
    }

    #[Then('la commune publiée de :name est :municipality, en :country')]
    public function theMunicipalityPublishedOf(string $name, string $municipality, string $country): void
    {
        $organizationId = $this->scenario->idOf($name);
        $last = array_last(array_filter($this->application->events->published(OrganizationFactsChanged::class), static fn (OrganizationFactsChanged $facts): bool => $facts->organizationId === $organizationId));

        $last = Expect::present($last, sprintf('No facts of "%s" were published', $name));
        Expect::same([$municipality, $country], [$last->municipalityCode, $last->country]);
    }

    #[Then('les contacts publiés de :name sont :contacts')]
    public function theContactsPublishedOf(string $name, string $contacts): void
    {
        $organizationId = $this->scenario->idOf($name);
        $last = array_last(array_filter($this->application->events->published(OrganizationFactsChanged::class), static fn (OrganizationFactsChanged $facts): bool => $facts->organizationId === $organizationId));

        $last = Expect::present($last, sprintf('No facts of "%s" were published', $name));
        Expect::same($contacts, implode(', ', array_map(fn (string $id): string => $this->scenario->nameOfPerson(new PersonId($id)), $last->contacts ?? [])));
    }

    #[Then('/^le fait publié de cet échange est présent, (?<status>planifié|fait|manqué|annulé), noté le "(?<moment>[^"]+)", révision (?<revision>\d+)$/')]
    public function theFactsOfThePresentExchange(string $status, string $moment, int $revision): void
    {
        $this->expectTheLastFactsOfTheExchange(['present' => true, 'status' => self::STATUSES[$status], 'notedAt' => $moment, 'revision' => $revision]);
    }

    #[Then('le fait publié de cet échange est absent, révision :revision')]
    public function theFactsOfTheErasedExchange(int $revision): void
    {
        $this->expectTheLastFactsOfTheExchange(['present' => false, 'status' => null, 'notedAt' => null, 'revision' => $revision]);
    }

    /**
     * @param array{present: bool, status: ?string, notedAt: ?string, revision: int} $expected with the moment written in French
     */
    private function expectTheLastFactsOfTheExchange(array $expected): void
    {
        $exchangeId = $this->scenario->theExchange();
        $published = array_filter($this->application->events->published(ExchangeFactsChanged::class), static fn (ExchangeFactsChanged $facts): bool => $facts->exchangeId === $exchangeId);
        $last = array_last($published);

        $last = Expect::present($last, 'No facts of the exchange were published');
        Expect::same($expected, ['present' => $last->present, 'status' => $last->status, 'notedAt' => null === $last->notedAt ? null : Vocabulary::describeFrenchMoment($last->notedAt), 'revision' => $last->revision]);
    }
}
