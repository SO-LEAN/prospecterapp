<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\ConsultWhatIsToProcess;
use Prospecter\Core\Prospecting\Application\Agenda\ConsultWhatIsToProcess\WhatIsToProcess;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The exchanges that the signed-in prospector planned and whose end has passed. Nobody has marked them
 * done, missed or cancelled yet.
 */
final class WhatIsToProcessContext implements Context
{
    use SharesTheScenario;
    use DescribesInterlocutors;

    private ?WhatIsToProcess $toProcess = null;

    #[When('/^je consulte (?:mes échanges|ce que j\'ai) à traiter$/')]
    public function iConsultMyExchangesToProcess(): void
    {
        $this->toProcess = $this->application->consultWhatIsToProcess(new ConsultWhatIsToProcess($this->application->signedIn(), $this->application->readingNow()));
    }

    #[Then('/^il y a "(?<text>[^"]+)", (?<overdue>pour aujourd\'hui|en retard d\'un jour|en retard de \d+ jours)$/')]
    public function thereIs(string $text, string $overdue): void
    {
        $toDo = array_find($this->toProcess()->toDos, static fn (ToDoInList $toDo): bool => $toDo->text === $text);
        $toDo = Expect::present($toDo, sprintf('"%s" is not to process', $text));

        Expect::same($overdue, match ($days = $this->toProcess()->daysOverdue[$toDo->id] ?? null) {
            0 => 'pour aujourd\'hui',
            1 => 'en retard d\'un jour',
            default => sprintf('en retard de %s jours', $days ?? 'aucun'),
        });
    }

    #[Then('mes actions à traiter sont :')]
    public function myToDosToProcessAre(TableNode $table): void
    {
        Expect::same($table->getColumnsHash(), array_map(fn (ToDoInList $toDo): array => [
            'texte' => $toDo->text,
            'retard' => match ($days = $this->toProcess()->daysOverdue[$toDo->id] ?? null) {
                0 => 'aujourd\'hui',
                1 => '1 jour',
                default => sprintf('%s jours', $days ?? 'aucun'),
            },
        ], $this->toProcess()->toDos));
    }

    #[Then('je n\'ai rien à traiter')]
    public function iHaveNothingToProcess(): void
    {
        Expect::same([[], [], []], [$this->toProcess()->exchanges, $this->toProcess()->toDos, $this->toProcess()->contactsToAttach]);
    }

    #[Then('/^il y a "(?<name>[^"]+)" à rattacher, effacée? le (?<day>\S+) sans rattachement$/')]
    public function thereIsAContactToAttach(string $name, string $day): void
    {
        $toAttach = array_find($this->toProcess()->contactsToAttach, fn (ContactToAttachInList $contact): bool => $contact->personId === $this->scenario->personIdOf($name));
        $toAttach = Expect::present($toAttach, sprintf('"%s" is not to attach', $name));

        Expect::same($day, $toAttach->erasableFrom->setTimezone($this->application->readerClock())->format('d/m/Y'));
    }

    #[Then('/^il n\'y a plus "(?<name>[^"]+)" à rattacher$/')]
    public function thereIsNoLongerAContactToAttach(string $name): void
    {
        Expect::same([], array_values(array_filter($this->toProcess()->contactsToAttach, fn (ContactToAttachInList $contact): bool => $contact->personId === $this->scenario->personIdOf($name))));
    }

    #[Then('ils sont, du plus ancien au plus récent :')]
    public function theyAre(TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange), $columns)), $this->toProcess()->exchanges));
    }

    #[Then('/^je n\'ai (?:plus|pas) d\'échange à traiter$/')]
    public function iHaveNoExchangeToProcess(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
        $this->iConsultMyExchangesToProcess();

        Expect::same([], $this->toProcess()->exchanges);
    }

    private function toProcess(): WhatIsToProcess
    {
        return Expect::present($this->toProcess, sprintf('The exchanges to process were expected, got: %s', $this->application->refusal()?->getMessage()));
    }

    /**
     * @return array<string, string>
     */
    private function describe(ExchangeInTimeline $exchange): array
    {
        return [
            'moment' => Vocabulary::describeFrenchMoment($exchange->occurredAt),
            'canal' => Vocabulary::describeChannel($exchange->channel),
            'avec' => null === $exchange->personId ? '' : $this->scenario->nameOfPersonAsRead(new PersonId($exchange->personId)),
            ...$this->shownInterlocutorOf($exchange, $this->toProcess()->interlocutors),
        ];
    }
}
