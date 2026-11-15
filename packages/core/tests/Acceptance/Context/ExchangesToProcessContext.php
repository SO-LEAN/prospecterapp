<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess\ConsultExchangesToProcess;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess\ExchangesToProcess;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The exchanges the signed-in prospector planned, whose end has passed, still to mark done, missed or cancelled.
 */
final class ExchangesToProcessContext implements Context
{
    use SharesTheScenario;

    private ?ExchangesToProcess $toProcess = null;

    #[When('je consulte mes échanges à traiter')]
    public function iConsultMyExchangesToProcess(): void
    {
        $this->toProcess = $this->application->consultExchangesToProcess(new ConsultExchangesToProcess($this->application->signedIn(), $this->application->now()));
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

    private function toProcess(): ExchangesToProcess
    {
        Expect::that(null !== $this->toProcess, sprintf('The exchanges to process were expected, got: %s', $this->application->refusal()?->getMessage()));

        return $this->toProcess;
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
        ];
    }
}
