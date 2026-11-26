<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Exchange\CorrectExchange\CorrectExchange;
use Prospecter\Core\Prospecting\Application\Exchange\CorrectExchangeText\CorrectExchangeText;
use Prospecter\Core\Prospecting\Application\Exchange\CorrectExchangeVenue\CorrectExchangeVenue;
use Prospecter\Core\Prospecting\Application\Exchange\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Application\Exchange\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\Exchange\NoteOrganizationExchange\NoteOrganizationExchange;
use Prospecter\Core\Prospecting\Application\PersonDescription;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * One exchange, "cet échange" in the scenarios: its detail, its correction and its erasure.
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
     * Corrects the exchange as the client does. It sends the exchange's current facts, except those the table
     * changes.
     */
    #[When('je corrige cet échange :')]
    public function iCorrectTheExchange(TableNode $table): void
    {
        $values = Vocabulary::rows($table);
        $facts = StateOf::the($this->application->exchanges->get(new ExchangeId($this->scenario->theExchange())))->object('facts', ExchangeFacts::class);
        $this->application->dispatch(new CorrectExchange(
            prospectorId: $this->application->signedIn(),
            id: $this->scenario->theExchange(),
            channel: isset($values['canal']) ? Vocabulary::channel($values['canal']) : $facts->channel->value,
            direction: isset($values['sens']) ? Vocabulary::direction($values['sens']) : $facts->direction->value,
            occurredAt: isset($values['moment']) ? Vocabulary::frenchMoment($values['moment']) : $facts->occurredAt,
            text: $values['texte'] ?? $facts->text,
            missed: isset($values['non abouti']) ? !in_array($values['non abouti'], self::NO, true) : ExchangeStatus::Missed === $this->storedStatus(),
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
        $facts = StateOf::the($this->application->exchanges->get(new ExchangeId($this->scenario->theExchange())))->object('facts', ExchangeFacts::class);
        $this->application->dispatch(new CorrectExchange(
            prospectorId: $this->application->signedIn(),
            id: $this->scenario->theExchange(),
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            occurredAt: $facts->occurredAt,
            text: Vocabulary::rows($table)['texte'],
            missed: ExchangeStatus::Missed === $this->storedStatus(),
            executedAt: $this->application->now(),
            madeConform: true,
        ));
    }

    private function storedStatus(): ExchangeStatus
    {
        return StateOf::the($this->application->exchanges->get(new ExchangeId($this->scenario->theExchange())))->object('status', ExchangeStatus::class);
    }

    #[When('je corrige le lieu de cet échange en :place')]
    public function iCorrectThePlace(string $place): void
    {
        $this->application->dispatch(new CorrectExchangeVenue($this->application->signedIn(), $this->scenario->theExchange(), $place, null, $this->application->now()));
    }

    #[When('je corrige le lien de cet échange en :link')]
    public function iCorrectTheLink(string $link): void
    {
        $this->application->dispatch(new CorrectExchangeVenue($this->application->signedIn(), $this->scenario->theExchange(), null, $link, $this->application->now()));
    }

    #[Then('le détail du rendez-vous propose l\'itinéraire vers :place')]
    public function theDetailLeadsToThePlace(string $place): void
    {
        Expect::same($place, $this->consulted()->place);
    }

    #[Then('le détail de la visio propose de la rejoindre à :link')]
    public function theDetailOffersToJoin(string $link): void
    {
        Expect::same($link, $this->consulted()->link);
    }

    #[When('je supprime cet échange')]
    public function iEraseTheExchange(): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));
    }

    #[Then('je vois l\'échange :')]
    public function iSeeTheExchange(TableNode $table): void
    {
        $expected = Vocabulary::rows($table);

        Expect::same($expected, array_replace($expected, array_intersect_key($this->describe($this->consulted()), $expected)));
    }

    #[Then('il nomme :')]
    public function itNames(TableNode $table): void
    {
        $exchange = $this->consulted();
        $participants = Expect::present($this->application->consultExchangeParticipants(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now())), 'The participants were expected');
        $named = [
            'interlocuteur' => null === $exchange->personId ? (string) $participants->organization?->fullName : self::named($participants->persons->contacts[$exchange->personId] ?? null),
            'noté par' => self::named($participants->persons->members[$exchange->createdBy] ?? null),
            'modifié par' => null === $exchange->correctedBy ? '' : self::named($participants->persons->members[$exchange->correctedBy] ?? null),
            'rendu conforme par' => null === $exchange->madeConformBy ? '' : self::named($participants->persons->members[$exchange->madeConformBy] ?? null),
        ];
        $expected = Vocabulary::rows($table);

        Expect::same($expected, array_intersect_key($named, $expected));
    }

    private static function named(?PersonDescription $person): string
    {
        if (null === $person) {
            return 'personne';
        }

        $name = trim(sprintf('%s %s', $person->firstName, $person->lastName));

        return null === $person->email ? $name : sprintf('%s <%s>', $name, $person->email);
    }

    #[Then('je peux sur l\'échange :')]
    public function iMayOnTheExchange(TableNode $table): void
    {
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->consulted()->possibleIntents));
    }

    /**
     * The exchange as it is now. It is read again after the scenario's steps.
     */
    private function consulted(): ExchangeDetail
    {
        $detail = $this->consult();

        return Expect::present($detail, sprintf('The exchange was expected, got: %s', $this->application->refusal()?->getMessage()));
    }

    private function consult(): ?ExchangeDetail
    {
        return $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->scenario->theExchange(), $this->application->now()));
    }

    /**
     * @return array<string, string>
     */
    private function describe(ExchangeDetail $exchange): array
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
            'provenance' => Vocabulary::describeProvenance($exchange->importedFrom),
            'badge' => Vocabulary::describeBadge($exchange->createdByAnAI),
            'rendu conforme' => null === $exchange->madeConformAt || null === $exchange->madeConformBy ? '' : Vocabulary::describeDoneBy($exchange->madeConformAt, $this->scenario->nameOfMember(new MemberId($exchange->madeConformBy))),
        ];
    }

    /**
     * The contact the exchange was with. Otherwise, the organization itself, the only one the exchange is kept for.
     */
    private function interlocutorOf(ExchangeDetail $exchange): string
    {
        return null === $exchange->personId
            ? $this->scenario->nameOf(new OrganizationId($exchange->organizationIds[0] ?? ''))
            : $this->scenario->nameOfPersonAsRead(new PersonId($exchange->personId));
    }
}
