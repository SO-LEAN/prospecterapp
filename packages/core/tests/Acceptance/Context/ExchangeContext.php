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
use Prospecter\Core\Prospecting\Application\ConsultOrganizationUpcomingExchanges\ConsultOrganizationUpcomingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\ConsultOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges\ConsultUpcomingExchanges;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange;
use Prospecter\Core\Prospecting\Application\PlanExchange\PlanExchange;
use Prospecter\Core\Prospecting\Application\PlanOrganizationExchange\PlanOrganizationExchange;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
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
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryPrivateStorage;
use Tests\Core\Double\StateOf;

/**
 * The exchanges with the contacts and with the organizations, noted or planned, and read in their timeline: a
 * timeline is named after its contact or its organization, those to come apart from the past ones.
 */
final class ExchangeContext implements Context
{
    use SharesTheScenario;

    public const string ANY_EXCHANGE = 'un appel|un SMS|un e-mail|un message LinkedIn|un rendez-vous';
    private const string ANY_PLANNED = self::ANY_EXCHANGE.'|une visio';

    private ?string $lastNoted = null;
    private string $lastNotedWith = '';
    private ?Timeline $timeline = null;
    /** @var list<string> */
    private array $notedWithTheOrganization = [];
    private ?InMemoryDatabase $databaseBeforeDeliveredAgain = null;
    private ?InMemoryPrivateStorage $privateStorageBeforeDeliveredAgain = null;
    private string $timelineOf = '';
    /** @var list<ExchangeInTimeline>|null */
    private ?array $overlapping = null;

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
            $id = $this->application->newIdentity();
            $this->application->exchanges->save(Exchange::notedWithTheOrganization($prospect, $this->scenario->meAsProspector()->id, new ExchangeId($id), $facts, $this->application->now()));
            $this->notedWithTheOrganization[] = $id;
        }
    }

    #[Given('/^(?<done>j\'ai planifié|je planifie) (?<exchange>'.self::ANY_PLANNED.') avec "(?<interlocutor>[^"]+)" le (?<day>\S+) à (?<time>\S+)(?: pour (?<duration>.+))?$/')]
    public function iPlanAnExchange(string $done, string $exchange, string $interlocutor, string $day, string $time, string $duration = ''): void
    {
        $minutes = '' === $duration ? null : Vocabulary::minutes($duration);
        $this->plan($this->application->signedIn(), $interlocutor, $interlocutor, Vocabulary::channelOfAnExchange($exchange), Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)), $minutes);

        if ('j\'ai planifié' === $done) {
            $this->expectPlanned();
        }
    }

    #[When('/^je planifie depuis "(?<organization>[^"]+)" (?<exchange>'.self::ANY_PLANNED.') avec "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function iPlanFromTheOrganization(string $organization, string $exchange, string $contact, string $day, string $time): void
    {
        $this->plan($this->application->signedIn(), $organization, $contact, Vocabulary::channelOfAnExchange($exchange), Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)), null);
    }

    #[Given('/^"(?<member>[^"]+)" a planifié (?<exchange>'.self::ANY_PLANNED.') avec "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function aMemberPlannedAnExchange(string $member, string $exchange, string $contact, string $day, string $time): void
    {
        $this->plan($this->scenario->memberIdOf($member)->value, $contact, $contact, Vocabulary::channelOfAnExchange($exchange), Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)), null);
        $this->expectPlanned();
    }

    #[Then('les échanges à venir de :name sont, du plus proche au plus lointain :')]
    public function theUpcomingExchangesAre(string $name, TableNode $table): void
    {
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange, $name), $columns)), $this->upcomingOf($name)));
    }

    #[Then('cet échange n\'est pas dans ses échanges passés')]
    public function theExchangeIsNotAmongThePastOnes(): void
    {
        $this->expectPlanned();

        Expect::that(!array_any($this->timelineOfNow($this->lastNotedWith), fn (ExchangeInTimeline $exchange): bool => $exchange->id === $this->lastNoted), 'The planned exchange is among the past ones');
    }

    #[Then('il est dans les échanges à venir de :name')]
    public function itIsAmongTheUpcomingExchangesOf(string $name): void
    {
        $this->expectPlanned();

        Expect::that(array_any($this->upcomingOf($name), fn (ExchangeInTimeline $exchange): bool => $exchange->id === $this->lastNoted), sprintf('The exchange is not among the upcoming ones of %s', $name));
    }

    #[Then('il dure de :start à :end')]
    public function itLastsFrom(string $start, string $end): void
    {
        $planned = array_find($this->upcomingOf($this->lastNotedWith), fn (ExchangeInTimeline $exchange): bool => $exchange->id === $this->lastNoted);
        $from = $planned?->occurredAt;

        Expect::same([$start, $end], [$from?->format('G\hi'), $from?->modify(sprintf('+%d minutes', $planned->durationInMinutes))->format('G\hi')]);
    }

    #[Then('j\'en suis le titulaire')]
    public function iHoldIt(): void
    {
        $this->expectPlanned();
        $planned = $this->application->database->exchange((string) $this->lastNoted);

        Expect::same($this->application->signedIn(), null === $planned ? null : StateOf::the($planned)['createdBy']->value);
    }

    #[When('/^je regarde ce qui chevauche le (?<day>\S+) à (?<time>\S+) pour (?<duration>.+)$/')]
    public function iLookAtWhatOverlaps(string $day, string $time, string $duration): void
    {
        $startsAt = Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time));
        $this->overlapping = $this->application->consultOverlappingExchanges(new ConsultOverlappingExchanges($this->application->signedIn(), $startsAt, Vocabulary::minutes($duration)))?->exchanges;
    }

    #[Then('je vois ces échanges qui le chevauchent :')]
    public function iSeeTheseOverlappingExchanges(TableNode $table): void
    {
        Expect::that(null !== $this->overlapping, sprintf('The overlapping exchanges were expected, got: %s', $this->application->refusal()?->getMessage()));
        $columns = array_flip($table->getRow(0));

        Expect::same($table->getColumnsHash(), array_map(fn (ExchangeInTimeline $exchange): array => array_replace($columns, array_intersect_key($this->describe($exchange, ''), $columns)), $this->overlapping));
    }

    #[Then('rien ne le chevauche')]
    public function nothingOverlapsIt(): void
    {
        Expect::same([], $this->overlapping);
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

        Expect::same($names[1], array_map($this->scenario->nameOf(...), null === $noted ? [] : StateOf::the($noted)['keptFor']));
    }

    #[Then('l\'échange avec :contact n\'existe plus')]
    public function theExchangeWithTheContactIsGone(string $contact): void
    {
        Expect::same([], $this->storedTimelineOf($contact));
    }

    #[Then('ses :count échanges sont effacés')]
    public function itsExchangesAreErased(int $count): void
    {
        Expect::same($count, count($this->notedWithTheOrganization));
        Expect::same([], array_filter($this->notedWithTheOrganization, fn (string $id): bool => null !== $this->application->database->exchange($id)));
    }

    #[Then(':count échanges à effacer sont publiés')]
    public function exchangesToEraseArePublished(int $count): void
    {
        Expect::same($count, count($this->application->events->published(ExchangeLeftWithoutOrganization::class)));
    }

    #[When('l\'échange à effacer est publié de nouveau')]
    public function theExchangeToEraseIsPublishedAgain(): void
    {
        $published = $this->application->events->published(ExchangeLeftWithoutOrganization::class);
        Expect::that([] !== $published, 'No exchange to erase was published');
        $this->databaseBeforeDeliveredAgain = $this->application->database->snapshot();
        $this->privateStorageBeforeDeliveredAgain = clone $this->application->privateStorage;
        $this->application->publishAgain(array_last($published));
    }

    #[Then('rien de plus n\'est écrit')]
    public function nothingMoreIsWritten(): void
    {
        Expect::that($this->application->database == $this->databaseBeforeDeliveredAgain, 'Something more is stored');
        Expect::that($this->application->privateStorage == $this->privateStorageBeforeDeliveredAgain, 'Something more is kept in the private storage');
    }

    #[Then('l\'appel avec :contact est toujours dans sa timeline, gardé pour :organization')]
    public function theCallIsStillKeptFor(string $contact, string $organization): void
    {
        $kept = array_map(fn (Exchange $exchange): array => [StateOf::the($exchange)['facts']->channel, array_map($this->scenario->nameOf(...), StateOf::the($exchange)['keptFor'])], $this->storedTimelineOf($contact));

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

    /**
     * With a contact, or from the card of an organization, with it or with one of its contacts.
     */
    private function plan(string $prospectorId, string $from, string $interlocutor, string $channel, \DateTimeImmutable $plannedAt, ?int $minutes): void
    {
        $id = $this->application->newIdentity();
        $personId = $this->scenario->isAnOrganization($interlocutor) ? null : $this->scenario->personIdOf($interlocutor);
        $this->application->dispatch($this->scenario->isAnOrganization($from)
            ? new PlanOrganizationExchange($prospectorId, $id, $this->scenario->idOf($from), $personId, $channel, $plannedAt, $minutes, null, $this->application->now())
            : new PlanExchange($prospectorId, $id, (string) $personId, $channel, $plannedAt, $minutes, null, $this->application->now()));
        $this->lastNoted = null === $this->application->refusal() ? $id : null;
        $this->lastNotedWith = $interlocutor;
        $this->scenario->rememberTheExchange($id);
    }

    private function expectPlanned(): void
    {
        Expect::that(null !== $this->lastNoted, sprintf('An exchange was expected to be planned, got: %s', $this->application->refusal()?->getMessage()));
    }

    /**
     * @return list<ExchangeInTimeline>
     */
    private function upcomingOf(string $name): array
    {
        $prospector = $this->application->signedIn();
        $upcoming = $this->scenario->isAnOrganization($name)
            ? $this->application->consultOrganizationUpcomingExchanges(new ConsultOrganizationUpcomingExchanges($prospector, $this->scenario->idOf($name), $this->application->now()))
            : $this->application->consultUpcomingExchanges(new ConsultUpcomingExchanges($prospector, $this->scenario->personIdOf($name), $this->application->now()));
        Expect::that(null !== $upcoming, sprintf('The upcoming exchanges were expected, got: %s', $this->application->refusal()?->getMessage()));

        return $upcoming->exchanges;
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
            ? $this->application->consultOrganizationTimeline(new ConsultOrganizationTimeline($prospector, $this->scenario->idOf($name), $this->application->now(), $after))
            : $this->application->consultTimeline(new ConsultTimeline($prospector, $this->scenario->personIdOf($name), $this->application->now(), $after));
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
        usort($stored, static fn (Exchange $a, Exchange $b): int => [StateOf::the($b)['facts']->occurredAt, (string) $b->id()] <=> [StateOf::the($a)['facts']->occurredAt, (string) $a->id()]);

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
            'durée' => Vocabulary::describeDuration($exchange->durationInMinutes),
            'statut' => Vocabulary::describeStatus($exchange->status),
            'de' => Vocabulary::describeFrenchMoment($exchange->occurredAt),
            'à' => Vocabulary::describeFrenchMoment($exchange->occurredAt->modify(sprintf('+%d minutes', $exchange->durationInMinutes ?? 0))),
            'sens' => Vocabulary::describeDirection($exchange->direction),
            'texte' => (string) $exchange->text,
            'signalé' => Vocabulary::describeYesOrNo($exchange->textFlagged),
            'contact' => Vocabulary::describeContactPresence($exchange->contactPresence),
            'texte à rendre conforme' => Vocabulary::describeYesOrNo($exchange->textToMakeConform),
            'à rendre conforme' => Vocabulary::describeYesOrNo($exchange->toMakeConform),
        ];
    }
}
