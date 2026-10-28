<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Prospecter\Core\Prospecting\Application\CorrectExchange\CorrectExchange;
use Prospecter\Core\Prospecting\Application\CorrectExchangeText\CorrectExchangeText;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * One exchange, "cet échange" of the scenarios: its detail, its correction, its erasure.
 */
final class ExchangeDetailContext implements Context
{
    use SharesTheScenario;

    private const array NO = ['', 'non'];

    #[Given('/^"(?<member>[^"]+)" a noté le (?<day>\S+) à (?<time>\S+) (?<exchange>'.ExchangeContext::ANY_EXCHANGE.') (?<direction>reçu|envoyé) (?:à|de) "(?<interlocutor>[^"]+)"(?<itself> elle-même)? à (?<occurredTime>\S+) : "(?<text>.*)"$/')]
    public function aMemberNotedAnExchange(string $member, string $day, string $time, string $exchange, string $direction, string $interlocutor, string $itself, string $occurredTime, string $text): void
    {
        $this->application->itIs(Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)));
        $this->scenario->rememberTheExchange($this->application->newIdentity());
        $prospectorId = $this->scenario->memberIdOf($member)->value;
        $channel = Vocabulary::channelOfAnExchange($exchange);
        $occurredAt = Vocabulary::frenchMoment(sprintf('%s à %s', $day, $occurredTime));
        $this->application->dispatch('' === $itself
            ? new NoteExchange($prospectorId, $this->scenario->theExchange(), $this->scenario->personIdOf($interlocutor), $channel, Vocabulary::direction($direction), $occurredAt, $text, false, $this->application->now())
            : new NoteOrganizationExchange($prospectorId, $this->scenario->theExchange(), $this->scenario->idOf($interlocutor), null, $channel, Vocabulary::direction($direction), $occurredAt, $text, false, $this->application->now()));
        Expect::that(null === $this->application->refusal(), sprintf('The exchange was expected to be noted, got: %s', $this->application->refusal()?->getMessage()));
    }

    #[When('je consulte cet échange')]
    public function iConsultTheExchange(): void
    {
        $this->consult();
    }

    /**
     * As the client corrects an exchange: its facts as they are, but those the table changes.
     */
    #[When('je corrige cet échange :')]
    public function iCorrectTheExchange(TableNode $table): void
    {
        $values = $table->getRowsHash();
        $facts = $this->application->exchanges->get(new ExchangeId($this->scenario->theExchange()))->facts();
        $this->application->dispatch(new CorrectExchange(
            prospectorId: $this->application->signedIn(),
            id: $this->scenario->theExchange(),
            channel: isset($values['canal']) ? Vocabulary::channel($values['canal']) : $facts->channel->value,
            direction: isset($values['sens']) ? Vocabulary::direction($values['sens']) : $facts->direction->value,
            occurredAt: isset($values['moment']) ? Vocabulary::frenchMoment($values['moment']) : $facts->occurredAt,
            text: $values['texte'] ?? $facts->text,
            missed: isset($values['non abouti']) ? !in_array($values['non abouti'], self::NO, true) : $facts->missed,
            executedAt: $this->application->now(),
        ));
    }

    #[When('je corrige le texte de cet échange en :text')]
    public function iCorrectTheTextOfTheExchange(string $text): void
    {
        $this->application->dispatch(new CorrectExchangeText($this->application->signedIn(), $this->scenario->theExchange(), $text, $this->application->now()));
    }

    #[When('je corrige le texte de cet échange en :text et le déclare rendu conforme')]
    public function iMakeTheTextConform(string $text): void
    {
        $this->application->dispatch(new CorrectExchangeText($this->application->signedIn(), $this->scenario->theExchange(), $text, $this->application->now(), madeConform: true));
    }

    #[When('je corrige cet échange et le déclare rendu conforme :')]
    public function iMakeTheExchangeConform(TableNode $table): void
    {
        $facts = $this->application->exchanges->get(new ExchangeId($this->scenario->theExchange()))->facts();
        $this->application->dispatch(new CorrectExchange(
            prospectorId: $this->application->signedIn(),
            id: $this->scenario->theExchange(),
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            occurredAt: $facts->occurredAt,
            text: $table->getRowsHash()['texte'],
            missed: $facts->missed,
            executedAt: $this->application->now(),
            madeConform: true,
        ));
    }

    #[When('je supprime cet échange')]
    public function iEraseTheExchange(): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));
    }

    #[Then('je vois l\'échange :')]
    public function iSeeTheExchange(TableNode $table): void
    {
        $expected = $table->getRowsHash();

        Expect::same($expected, array_replace($expected, array_intersect_key($this->describe($this->consulted()), $expected)));
    }

    #[Then('je peux sur l\'échange :')]
    public function iMayOnTheExchange(TableNode $table): void
    {
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->consulted()->possibleIntents));
    }

    /**
     * The exchange as it is now, read again after what the scenario did.
     */
    private function consulted(): ExchangeDetailResponse
    {
        $detail = $this->consult();
        Expect::that(null !== $detail, sprintf('The exchange was expected, got: %s', $this->application->refusal()?->getMessage()));

        return $detail;
    }

    private function consult(): ?ExchangeDetailResponse
    {
        return $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange()));
    }

    /**
     * @return array<string, string>
     */
    private function describe(ExchangeDetailResponse $exchange): array
    {
        return [
            'interlocuteur' => $this->interlocutorOf($exchange),
            'canal' => Vocabulary::describeChannel($exchange->channel),
            'sens' => Vocabulary::describeDirection($exchange->direction),
            'moment' => Vocabulary::describeFrenchMoment($exchange->occurredAt),
            'texte' => (string) $exchange->text,
            'noté' => Vocabulary::describeDoneBy($exchange->createdAt, $this->scenario->nameOfMember(new MemberId($exchange->createdBy))),
            'modifié' => null === $exchange->correctedAt || null === $exchange->correctedBy ? '' : Vocabulary::describeDoneBy($exchange->correctedAt, $this->scenario->nameOfMember(new MemberId($exchange->correctedBy))),
            'signalé' => Vocabulary::describeYesOrNo($exchange->textFlagged),
            'contact' => Vocabulary::describeContactPresence($exchange->contactPresence),
            'texte à rendre conforme' => Vocabulary::describeYesOrNo($exchange->textToMakeConform),
            'à rendre conforme' => Vocabulary::describeYesOrNo($exchange->toMakeConform),
            'rendu conforme' => null === $exchange->madeConformAt || null === $exchange->madeConformBy ? '' : Vocabulary::describeDoneBy($exchange->madeConformAt, $this->scenario->nameOfMember(new MemberId($exchange->madeConformBy))),
        ];
    }

    /**
     * The contact it was with, or the organization itself, the only one it is kept for.
     */
    private function interlocutorOf(ExchangeDetailResponse $exchange): string
    {
        return null === $exchange->personId
            ? $this->scenario->nameOf(new OrganizationId($exchange->organizationIds[0] ?? ''))
            : $this->scenario->nameOfPersonAsRead(new PersonId($exchange->personId));
    }
}
