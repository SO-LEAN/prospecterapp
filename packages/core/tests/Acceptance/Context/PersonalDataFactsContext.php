<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The facts that PersonalData publishes each time a contact changes (ADR 0046, 0099). The event is the contract.
 * The scenarios read the last event published about the person they name.
 */
final class PersonalDataFactsContext implements Context
{
    use SharesTheScenario;

    #[Then('/^le fait publié du contact "(?<name>[^"]+)" est (?<presence>présent|absent), révision (?<revision>\d+)$/')]
    public function theFactsOfTheContact(string $name, string $presence, int $revision): void
    {
        $personId = $this->scenario->personIdOf($name);
        $published = array_filter($this->application->events->published(ContactFactsChanged::class), static fn (ContactFactsChanged $facts): bool => $facts->personId === $personId);
        $last = array_last($published);

        $last = Expect::present($last, sprintf('No facts of "%s" were published', $name));
        Expect::same(
            ['present' => 'présent' === $presence, 'revision' => $revision, 'accountId' => $this->scenario->meAsProspector()->accountId->value],
            ['present' => $last->present, 'revision' => $last->revision, 'accountId' => $last->accountId],
        );
    }

    #[Then('le fait publié du contact :name le dit joignable par :reach')]
    public function theFactsOfTheContactSayItIsReachedBy(string $name, string $reach): void
    {
        $personId = $this->scenario->personIdOf($name);
        $last = array_last(array_filter($this->application->events->published(ContactFactsChanged::class), static fn (ContactFactsChanged $facts): bool => $facts->personId === $personId));

        $last = Expect::present($last, sprintf('No facts of "%s" were published', $name));
        Expect::same($reach, Vocabulary::describeReach($last->hasEmail, $last->hasPhone, $last->hasMobile, $last->hasLinkedIn));
    }

    #[Then('aucun fait de contact n\'est publié')]
    public function noFactsOfContactArePublished(): void
    {
        Expect::same([], $this->application->events->published(ContactFactsChanged::class));
    }
}
