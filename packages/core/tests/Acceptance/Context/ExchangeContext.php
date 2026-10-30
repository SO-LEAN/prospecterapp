<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The exchanges with the contacts and with the organizations, noted and read in their timeline: a timeline is
 * named after its contact or its organization.
 */
final class ExchangeContext implements Context
{
    use SharesTheScenario;

    public const string ANY_EXCHANGE = 'un appel|un SMS|un e-mail|un message LinkedIn|un rendez-vous';

    private ?string $lastNoted = null;
    private string $lastNotedWith = '';
    private ?Timeline $timeline = null;
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

    #[Given('/^(?:j\'ai noté|je note) (?<exchange>'.self::ANY_EXCHANGE.') (?<direction>reçu|envoyé) (?:à|de) "(?<organization>[^"]+)" elle-même(?: le (?<day>\S+) à (?<time>\S+))?$/')]
    public function iNoteAnExchangeWithTheOrganization(string $exchange, string $direction, string $organization, string $day = '', string $time = ''): void
    {
        $occurredAt = '' === $day ? $this->application->now() : Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time));
        $this->noteFrom($organization, null, Vocabulary::channelOfAnExchange($exchange), Vocabulary::direction($direction), $occurredAt);
    }

    #[When('/^je note depuis "(?<organization>[^"]+)" (?<exchange>'.self::ANY_EXCHANGE.') (?<direction>reçu|envoyé) (?:à|de) "(?<contact>[^"]+)"$/')]
    public function iNoteFromTheOrganization(string $organization, string $exchange, string $direction, string $contact): void
    {
        $this->noteFrom($organization, $this->scenario->personIdOf($contact), Vocabulary::channelOfAnExchange($exchange), Vocabulary::direction($direction), $this->application->now());
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

    #[Given('j\'ai noté :count échanges avec :organization elle-même')]
    public function iNotedManyExchangesWithTheOrganization(int $count, string $organization): void
    {
        $prospect = $this->scenario->organizationNamed($organization);

        foreach (range(1, $count) as $minutesAgo) {
            $facts = new ExchangeFacts(Channel::Call, Direction::Inbound, $this->application->now()->modify(sprintf('-%d minutes', $minutesAgo)));
            $this->application->exchanges->save(Exchange::notedWithTheOrganization($prospect, $this->scenario->meAsProspector()->id, new ExchangeId($this->application->newIdentity()), $facts, $this->application->now()));
        }
    }

    #[When('je consulte la timeline de :name')]
    public function iConsultTheTimeline(string $name): void
    {
        $this->timelineOf = $name;
        $this->timeline = $this->consultTimelineOf($name);
        $this->timelineIntents = $this->timeline?->possibleIntents;
    }

    #[When('je regarde ce que je peux faire de la timeline de :person')]
    public function iLookAtWhatIMayDoWithTheTimeline(string $person): void
    {
        $this->timelineIntents = $this->application->consultTimelineIntents(new ConsultTimelineIntents($this->application->signedIn(), $this->scenario->personIdOf($person)))?->possibleIntents;
    }

    #[Then('la timeline de :name est, du plus récent au plus ancien :')]
    public function theTimelineIs(string $name, TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));
        $described = array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange, $name), $columns)), $this->timelineOfNow($name));

        Expect::same($table->getColumnsHash(), $described);
    }

    #[Then('la timeline de :name est vide')]
    public function theTimelineIsEmpty(string $name): void
    {
        Expect::same([], $this->timelineOfNow($name));
    }

    #[Then('/^la timeline de "(?<name>[^"]+)" montre (?:ce|cet) (?:SMS|e-mail|appel|message LinkedIn|rendez-vous)$/')]
    public function theTimelineShowsTheLastNoted(string $name): void
    {
        Expect::that(null !== $this->lastNoted, sprintf('An exchange was expected to be noted, got: %s', $this->application->refusal()?->getMessage()));

        Expect::that(array_any($this->timelineOfNow($name), fn (ExchangeInTimeline $exchange): bool => $exchange->id === $this->lastNoted), sprintf('The timeline of %s does not show the exchange', $name));
    }

    #[Then('la timeline de :organization le montre avec l\'interlocuteur :interlocutor')]
    public function theTimelineShowsTheLastNotedWith(string $organization, string $interlocutor): void
    {
        $noted = array_find($this->timelineOfNow($organization), fn (ExchangeInTimeline $exchange): bool => $exchange->id === $this->lastNoted);

        Expect::same($interlocutor, null === $noted ? null : $this->interlocutorOf($noted, $organization));
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

        Expect::same(array_map(static fn (Exchange $exchange): string => $exchange->id()->value, $mostRecent), array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $this->timeline->exchanges));
    }

    #[Then('la timeline de :organization signale :count échanges à rendre conformes')]
    public function theTimelineCountsTheExchangesToMakeConform(string $organization, int $count): void
    {
        Expect::same($count, $this->consultTimelineOf($organization)?->toMakeConform);
    }

    #[When('je consulte les échanges à rendre conformes de :organization')]
    public function iConsultTheExchangesToMakeConform(string $organization): void
    {
        $this->timeline = $this->application->consultExchangesToMakeConform(new ConsultExchangesToMakeConform($this->application->signedIn(), $this->scenario->idOf($organization)));
    }

    #[Then('les échanges à rendre conformes de :organization sont, du plus récent au plus ancien :')]
    public function theExchangesToMakeConformAre(string $organization, TableNode $table): void
    {
        $this->iConsultTheExchangesToMakeConform($organization);
        Expect::that(null !== $this->timeline, sprintf('The exchanges to make conform were expected, got: %s', $this->application->refusal()?->getMessage()));
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange, $organization), $columns)), $this->timeline->exchanges));
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
        $following = $this->consultTimelineOf($this->timelineOf, after: $this->timeline->next);
        $older = array_filter($following->exchanges ?? [], static fn (ExchangeInTimeline $exchange): bool => $exchange->occurredAt < $last?->occurredAt);

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

    private function noteFrom(string $organization, ?string $personId, string $channel, string $direction, \DateTimeImmutable $occurredAt): void
    {
        $id = $this->application->newIdentity();
        $this->application->dispatch(new NoteOrganizationExchange($this->application->signedIn(), $id, $this->scenario->idOf($organization), $personId, $channel, $direction, $occurredAt, null, false, $this->application->now()));
        $this->lastNoted = null === $this->application->refusal() ? $id : null;
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
        $me = $this->scenario->meAsProspector();
        $exchange = Exchange::notedWithAContact($me->accountId, $me->id, new ExchangeId($this->application->newIdentity()), $this->scenario->personNamed($contact)->id(), $keptFor, $facts, $this->application->now());
        $this->application->exchanges->save($exchange);
    }

    private function consultTimelineOf(string $name, ?string $after = null): ?Timeline
    {
        $prospector = $this->application->signedIn();

        return $this->scenario->isAnOrganization($name)
            ? $this->application->consultOrganizationTimeline(new ConsultOrganizationTimeline($prospector, $this->scenario->idOf($name), $after))
            : $this->application->consultTimeline(new ConsultTimeline($prospector, $this->scenario->personIdOf($name), $after));
    }

    /**
     * @return list<ExchangeInTimeline>
     */
    private function timelineOfNow(string $name): array
    {
        $timeline = $this->consultTimelineOf($name);
        Expect::that(null !== $timeline, sprintf('A timeline was expected, got: %s', $this->application->refusal()?->getMessage()));

        return $timeline->exchanges;
    }

    /**
     * The exchanges saved for the timeline, the most recent moment first.
     *
     * @return list<Exchange>
     */
    private function storedTimelineOf(string $name): array
    {
        $stored = $this->scenario->isAnOrganization($name)
            ? $this->application->exchanges->keptFor($this->scenario->organizationNamed($name)->id())
            : $this->application->exchanges->withContact(new PersonId($this->scenario->personIdOf($name)), $this->scenario->meAsProspector()->accountId);
        usort($stored, static fn (Exchange $a, Exchange $b): int => [$b->facts()->occurredAt, (string) $b->id()] <=> [$a->facts()->occurredAt, (string) $a->id()]);

        return $stored;
    }

    /**
     * Who communicated: the contact, or the organization of the timeline itself.
     */
    private function interlocutorOf(ExchangeInTimeline $exchange, string $timelineOf): string
    {
        return null === $exchange->personId ? $timelineOf : $this->scenario->nameOfPersonAsRead(new PersonId($exchange->personId));
    }

    /**
     * @return array<string, string>
     */
    private function describe(ExchangeInTimeline $exchange, string $timelineOf): array
    {
        return [
            'moment' => Vocabulary::describeFrenchMoment($exchange->occurredAt),
            'interlocuteur' => $this->interlocutorOf($exchange, $timelineOf),
            'canal' => Vocabulary::describeChannel($exchange->channel),
            'sens' => Vocabulary::describeDirection($exchange->direction),
            'texte' => (string) $exchange->text,
            'signalé' => Vocabulary::describeYesOrNo($exchange->textFlagged),
            'contact' => Vocabulary::describeContactPresence($exchange->contactPresence),
            'texte à rendre conforme' => Vocabulary::describeYesOrNo($exchange->textToMakeConform),
            'à rendre conforme' => Vocabulary::describeYesOrNo($exchange->toMakeConform),
        ];
    }
}
