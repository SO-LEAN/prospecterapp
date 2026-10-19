<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ExchangeResponse;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\TimelineResponse;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The exchanges with the contacts, noted and read in their timeline.
 */
final class ExchangeContext implements Context
{
    use SharesTheScenario;

    private const string ANY_EXCHANGE = 'un appel|un SMS|un e-mail|un message LinkedIn|un rendez-vous';

    private ?string $lastNoted = null;
    private string $lastNotedWith = '';
    private ?TimelineResponse $timeline = null;
    private string $timelineOf = '';

    /**
     * @var list<string>|null
     */
    private ?array $timelineIntents = null;

    #[Given('/^(?:j\'ai noté|je note) (?<exchange>'.self::ANY_EXCHANGE.') (?<direction>reçu|envoyé) (?:à|de) "(?<contact>[^"]+)"(?: le (?<day>\S+) à (?<time>\S+))?(?<missed> qui n\'a pas abouti)?$/')]
    public function iNoteAnExchange(string $exchange, string $direction, string $contact, string $day = '', string $time = '', string $missed = ''): void
    {
        $occurredAt = '' === $day ? $this->application->now() : Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time));
        $this->note($contact, Vocabulary::channelOfAnExchange($exchange), Vocabulary::direction($direction), $occurredAt, null, '' !== $missed);
    }

    #[When('je note un échange avec :contact :')]
    public function iNoteAnExchangeWith(string $contact, TableNode $table): void
    {
        $values = $table->getRowsHash();
        $this->note($contact, Vocabulary::channel($values['canal']), Vocabulary::direction($values['sens']), Vocabulary::frenchMoment($values['moment']), $values['texte'] ?? null, false);
    }

    #[Given('/^j\'ai noté (?<exchange>'.self::ANY_EXCHANGE.') avec "(?<contact>[^"]+)", gardé pour (?<organizations>.+?)(?: seulement)?$/')]
    public function iNotedAnExchangeKeptFor(string $exchange, string $contact, string $organizations): void
    {
        preg_match_all('/"([^"]+)"/', $organizations, $names);
        $keptFor = array_map(fn (string $name): OrganizationId => $this->scenario->organizationNamed($name)->id(), $names[1]);
        $this->arrange($contact, $keptFor, new ExchangeFacts(Channel::from(Vocabulary::channelOfAnExchange($exchange)), Direction::Inbound, $this->application->now()));
    }

    #[Given('j\'ai noté :count échanges avec :contact')]
    public function iNotedManyExchanges(int $count, string $contact): void
    {
        $keptFor = array_map(static fn (Organization $organization): OrganizationId => $organization->id(), $this->application->organizations->withContacts([$this->scenario->personNamed($contact)->id()], $this->scenario->meAsProspector()->accountId));

        foreach (range(1, $count) as $minutesAgo) {
            $this->arrange($contact, $keptFor, new ExchangeFacts(Channel::Sms, Direction::Inbound, $this->application->now()->modify(sprintf('-%d minutes', $minutesAgo))));
        }
    }

    #[When('je consulte la timeline de :contact')]
    public function iConsultTheTimeline(string $contact): void
    {
        $this->timelineOf = $contact;
        $this->timeline = $this->application->consultTimeline(new ConsultTimeline($this->application->signedIn(), $this->scenario->personIdOf($contact)));
        $this->timelineIntents = $this->timeline?->possibleIntents;
    }

    #[When('je regarde ce que je peux faire de la timeline de :person')]
    public function iLookAtWhatIMayDoWithTheTimeline(string $person): void
    {
        $this->timelineIntents = $this->application->consultTimelineIntents(new ConsultTimelineIntents($this->application->signedIn(), $this->scenario->personIdOf($person)))?->possibleIntents;
    }

    #[Then('la timeline de :contact est, du plus récent au plus ancien :')]
    public function theTimelineIs(string $contact, TableNode $table): void
    {
        Expect::same($table->getColumnsHash(), array_map($this->describe(...), $this->timelineOfNow($contact)));
    }

    #[Then('la timeline de :contact montre un appel reçu manqué')]
    public function theTimelineShowsAMissedInboundCall(string $contact): void
    {
        $exchange = $this->timelineOfNow($contact)[0] ?? null;

        Expect::same(['call', 'inbound', true], [$exchange?->channel, $exchange?->direction, $exchange?->missed]);
    }

    #[Then('/^l\'échange est gardé pour (?<organizations>.+)$/')]
    public function theExchangeIsKeptFor(string $organizations): void
    {
        preg_match_all('/"([^"]+)"/', $organizations, $names);
        $noted = array_find($this->storedTimelineOf($this->lastNotedWith), fn (Exchange $exchange): bool => $exchange->id()->value === $this->lastNoted);

        Expect::same($names[1], array_map($this->scenario->nameOf(...), $noted?->keptFor() ?? []));
    }

    #[Then('l\'échange avec :contact n\'existe plus')]
    public function theExchangeWithTheContactIsGone(string $contact): void
    {
        Expect::same([], $this->storedTimelineOf($contact));
    }

    #[Then('l\'appel avec :contact est toujours dans sa timeline, gardé pour :organization')]
    public function theCallIsStillKeptFor(string $contact, string $organization): void
    {
        $kept = array_map(fn (Exchange $exchange): array => [$exchange->facts()->channel, array_map($this->scenario->nameOf(...), $exchange->keptFor())], $this->storedTimelineOf($contact));

        Expect::same([[Channel::Call, [$organization]]], $kept);
    }

    #[Then('je vois les :count plus récents')]
    public function iSeeTheMostRecent(int $count): void
    {
        Expect::that(null !== $this->timeline, sprintf('A timeline was expected, got: %s', $this->application->refusal()?->getMessage()));
        $mostRecent = array_slice($this->storedTimelineOf($this->timelineOf), 0, $count);

        Expect::same(array_map(static fn (Exchange $exchange): string => $exchange->id()->value, $mostRecent), array_map(static fn (ExchangeResponse $exchange): string => $exchange->id, $this->timeline->exchanges));
    }

    #[Then('je peux sur la timeline :')]
    public function iMayOnTheTimeline(TableNode $table): void
    {
        Expect::same($table->getColumn(0), Vocabulary::describeIntents($this->offeredOnTheTimeline()));
    }

    #[Then('je ne peux rien sur la timeline')]
    public function iMayDoNothingOnTheTimeline(): void
    {
        Expect::same([], $this->offeredOnTheTimeline());
    }

    #[Then('je peux lire la suite après le dernier')]
    public function iMayReadWhatFollows(): void
    {
        Expect::that(null !== $this->timeline?->next, 'A next slice was expected');
        $last = array_last($this->timeline->exchanges);
        $following = $this->application->consultTimeline(new ConsultTimeline($this->application->signedIn(), $this->scenario->personIdOf($this->timelineOf), after: $this->timeline->next));
        $older = array_filter($following->exchanges ?? [], static fn (ExchangeResponse $exchange): bool => $exchange->occurredAt < $last?->occurredAt);

        Expect::same(count($this->storedTimelineOf($this->timelineOf)) - count($this->timeline->exchanges), count($older));
    }

    /**
     * @return list<string>
     */
    private function offeredOnTheTimeline(): array
    {
        Expect::that(null !== $this->timelineIntents, sprintf('What the timeline offers was expected, got: %s', $this->application->refusal()?->getMessage()));

        return $this->timelineIntents;
    }

    private function note(string $contact, string $channel, string $direction, \DateTimeImmutable $occurredAt, ?string $text, bool $missed): void
    {
        $id = $this->application->newIdentity();
        $this->application->dispatch(new NoteExchange($this->application->signedIn(), $id, $this->scenario->personIdOf($contact), $channel, $direction, $occurredAt, $text, $missed, $this->application->now()));
        $this->lastNoted = null === $this->application->refusal() ? $id : null;
        $this->lastNotedWith = $contact;
    }

    /**
     * @param list<OrganizationId> $keptFor
     */
    private function arrange(string $contact, array $keptFor, ExchangeFacts $facts): void
    {
        $exchange = Exchange::notedBy($this->scenario->meAsProspector(), new ExchangeId($this->application->newIdentity()), $this->scenario->personNamed($contact)->id(), $keptFor, $facts, $this->application->now());
        $this->application->exchanges->save($exchange);
    }

    /**
     * @return list<ExchangeResponse>
     */
    private function timelineOfNow(string $contact): array
    {
        $timeline = $this->application->consultTimeline(new ConsultTimeline($this->application->signedIn(), $this->scenario->personIdOf($contact)));
        Expect::that(null !== $timeline, sprintf('A timeline was expected, got: %s', $this->application->refusal()?->getMessage()));

        return $timeline->exchanges;
    }

    /**
     * @return list<Exchange>
     */
    private function storedTimelineOf(string $contact): array
    {
        return $this->application->exchanges->timelineOf(new PersonId($this->scenario->personIdOf($contact)), $this->scenario->meAsProspector()->accountId, null, PHP_INT_MAX)->content;
    }

    /**
     * @return array<string, string>
     */
    private function describe(ExchangeResponse $exchange): array
    {
        return [
            'moment' => Vocabulary::describeFrenchMoment($exchange->occurredAt),
            'canal' => Vocabulary::describeChannel($exchange->channel),
            'sens' => Vocabulary::describeDirection($exchange->direction),
            'texte' => (string) $exchange->text,
        ];
    }
}
