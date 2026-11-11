<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Tests\Core\Acceptance\Expect;

/**
 * The facts PersonalData publishes each time a contact changes (ADR 0046, 0099): the event is the contract, the
 * scenarios read the last one published about the person they name.
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

        Expect::that(null !== $last, sprintf('No facts of "%s" were published', $name));
        Expect::same(
            ['present' => 'présent' === $presence, 'revision' => $revision, 'accountId' => $this->scenario->meAsProspector()->accountId->value],
            ['present' => $last->present, 'revision' => $last->revision, 'accountId' => $last->accountId],
        );
    }

    #[Then('aucun fait de contact n\'est publié')]
    public function noFactsOfContactArePublished(): void
    {
        Expect::same([], $this->application->events->published(ContactFactsChanged::class));
    }
}
