<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Booking\Application\AddInactivePeriod\AddInactivePeriod;
use Prospecter\Core\Booking\Application\ChangeBookingLink\ChangeBookingLink;
use Prospecter\Core\Booking\Application\CloseBooking\CloseBooking;
use Prospecter\Core\Booking\Application\ConsultMyBookingPage\ConsultMyBookingPage;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPage;
use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\Booking\Application\OfferChannels\OfferChannels;
use Prospecter\Core\Booking\Application\OpenBooking\OpenBooking;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\Booking\Application\RemoveInactivePeriod\RemoveInactivePeriod;
use Prospecter\Core\Booking\Application\SetWeeklyWindows\SetWeeklyWindows;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;

/**
 * The booking page of the member signed in: when they may be booked, how, and the public link they give (#177).
 */
final class BookingContext implements Context
{
    use SharesTheScenario;

    private const array WEEKDAYS = ['lundi' => 'monday', 'mardi' => 'tuesday', 'mercredi' => 'wednesday', 'jeudi' => 'thursday', 'vendredi' => 'friday', 'samedi' => 'saturday', 'dimanche' => 'sunday'];
    private const array CHANNELS = ['Appel' => 'call', 'Visio' => 'videoCall'];
    private const string BASE62_OF_22 = '/^[0-9A-Za-z]{22}$/';
    private const string FIRST_DAY = '(?<first>\d{2}\/\d{2}\/\d{4})';
    private const string LAST_DAY = '(?<last>\d{2}\/\d{2}\/\d{4})';

    /** @var array<string, string> the inactive periods added, by their days as the scenario writes them */
    private array $periods = [];
    private ?string $formerCode = null;
    private ?PublicBookingPage $opened = null;

    #[When('je règle mes plages :')]
    public function iSetMyWindows(TableNode $table): void
    {
        $windows = array_map(static fn (array $row): array => ['weekday' => self::WEEKDAYS[$row['jour']], 'from' => $row['de'], 'to' => $row['à']], $table->getHash());
        $this->setWindows($windows, $this->application->readerClock()->getName());
    }

    #[Given('mes plages sont réglées')]
    public function myWindowsAreSet(): void
    {
        $this->setWindows([['weekday' => 'monday', 'from' => '14:00', 'to' => '17:00']], $this->application->readerClock()->getName());
    }

    #[When('/^je règle une plage le (?<day>\w+) de (?<from>\d{1,2}h\d{2}) à (?<to>\d{1,2}h\d{2})$/u')]
    public function iSetAWindow(string $day, string $from, string $to): void
    {
        $this->iSetAWindowInTimeZone($day, $from, $to, $this->application->readerClock()->getName());
    }

    #[When('/^je règle une plage le (?<day>\w+) de (?<from>\d{1,2}h\d{2}) à (?<to>\d{1,2}h\d{2}) à l\'heure de "(?<timeZone>[^"]+)"$/u')]
    public function iSetAWindowInTimeZone(string $day, string $from, string $to, string $timeZone): void
    {
        $this->setWindows([['weekday' => self::WEEKDAYS[$day], 'from' => self::time($from), 'to' => self::time($to)]], $timeZone);
    }

    #[Then('mes plages sont :')]
    public function myWindowsAre(TableNode $table): void
    {
        $expected = array_map(static fn (array $row): array => ['weekday' => self::WEEKDAYS[$row['jour']], 'from' => $row['de'], 'to' => $row['à']], $table->getHash());
        Expect::same($expected, $this->myPage()->windows);
    }

    #[Then('mes plages sont à l\'heure de :timeZone')]
    public function myWindowsAreInTimeZone(string $timeZone): void
    {
        Expect::same($timeZone, $this->myPage()->timeZone);
    }

    #[Then('je n\'ai aucune plage')]
    public function iHaveNoWindow(): void
    {
        Expect::same([], $this->myPage()->windows);
    }

    #[When('/^j\'ajoute une période inactive du '.self::FIRST_DAY.' au '.self::LAST_DAY.'$/')]
    #[Given('/^j\'ai ajouté une période inactive du '.self::FIRST_DAY.' au '.self::LAST_DAY.'$/')]
    public function iAddAnInactivePeriod(string $first, string $last): void
    {
        $id = $this->application->newIdentity();
        $this->periods[$first.'-'.$last] = $id;
        $this->application->dispatch(new AddInactivePeriod($this->me(), $id, self::day($first), self::day($last), $this->application->now()));
    }

    #[When('/^je retire la période inactive du '.self::FIRST_DAY.' au '.self::LAST_DAY.'$/')]
    public function iRemoveTheInactivePeriod(string $first, string $last): void
    {
        $this->removePeriod($this->periods[$first.'-'.$last]);
    }

    #[When('je retire une période inactive que je n\'ai pas')]
    public function iRemoveAnInactivePeriodIDoNotHave(): void
    {
        $this->removePeriod($this->application->newIdentity());
    }

    #[Then('/^on ne peut pas me réserver du '.self::FIRST_DAY.' au '.self::LAST_DAY.' inclus$/')]
    public function iAmUnavailableFromTo(string $first, string $last): void
    {
        $page = $this->application->bookingPages->get(new MemberId($this->me()));
        for ($day = new \DateTimeImmutable(self::day($first)); $day <= new \DateTimeImmutable(self::day($last)); $day = $day->modify('+1 day')) {
            Expect::that($page->isUnavailableOn($day->format('Y-m-d')), sprintf('Booked on %s', $day->format('Y-m-d')));
        }
    }

    #[Then('/^on peut me réserver le '.self::FIRST_DAY.' et le '.self::LAST_DAY.'$/')]
    public function iAmAvailableOn(string $first, string $last): void
    {
        $page = $this->application->bookingPages->get(new MemberId($this->me()));
        foreach ([$first, $last] as $day) {
            Expect::that(!$page->isUnavailableOn(self::day($day)), sprintf('Unavailable on %s', $day));
        }
    }

    #[Then('je n\'ai aucune période inactive')]
    public function iHaveNoInactivePeriod(): void
    {
        Expect::same([], $this->myPage()->inactivePeriods);
    }

    #[Then('mes périodes inactives sont :')]
    public function myInactivePeriodsAre(TableNode $table): void
    {
        $expected = array_map(static fn (array $row): array => ['firstDay' => self::day($row['du']), 'lastDay' => self::day($row['au'])], $table->getHash());
        $read = array_map(static fn (array $period): array => ['firstDay' => $period['firstDay'], 'lastDay' => $period['lastDay']], $this->myPage()->inactivePeriods);
        Expect::same($expected, $read);
    }

    #[When('je propose les canaux :channels')]
    public function iOfferTheChannels(string $channels): void
    {
        $this->offer(...array_map(static fn (string $label): string => self::CHANNELS[$label], explode(', ', $channels)));
    }

    #[When('je ne propose plus aucun canal')]
    public function iOfferNoChannel(): void
    {
        $this->offer();
    }

    #[Then('mes canaux proposés sont :channels')]
    public function myChannelsAre(string $channels): void
    {
        Expect::same(array_map(static fn (string $label): string => self::CHANNELS[$label], explode(', ', $channels)), $this->myPage()->channels);
    }

    #[Then('je ne propose aucun canal')]
    public function iOfferNoChannelAtAll(): void
    {
        Expect::same([], $this->myPage()->channels);
    }

    #[When('j\'active ma prise de rendez-vous, en proposant l\'appel et la visio')]
    #[Given('ma prise de rendez-vous est active')]
    public function iOpenOfferingCallAndVideoCall(): void
    {
        $this->offer('call', 'videoCall');
        $this->open();
    }

    #[Given('ma prise de rendez-vous est active, en proposant la visio')]
    public function iOpenOfferingVideoCall(): void
    {
        $this->offer('videoCall');
        $this->open();
    }

    #[When('j\'active ma prise de rendez-vous sans proposer ni l\'appel ni la visio')]
    public function iOpenOfferingNothing(): void
    {
        $this->offer();
        $this->open();
    }

    #[When('je l\'active à nouveau')]
    public function iOpenAgain(): void
    {
        $this->formerCode = $this->myPage()->code;
        $this->open();
    }

    #[When('je la coupe')]
    #[Given('je l\'ai coupée')]
    public function iClose(): void
    {
        $this->application->dispatch(new CloseBooking($this->me(), $this->application->now()));
    }

    #[When('je change de lien')]
    public function iChangeTheLink(): void
    {
        $this->formerCode = $this->myPage()->code;
        $this->application->dispatch(new ChangeBookingLink($this->me(), $this->application->newBookingCode(), $this->application->now()));
    }

    #[Then('mon lien de prise de rendez-vous est actif')]
    public function myBookingIsOpen(): void
    {
        Expect::that($this->myPage()->open, 'The booking page is closed');
    }

    #[Then('mon lien de prise de rendez-vous est coupé')]
    public function myBookingIsClosed(): void
    {
        Expect::that(!$this->myPage()->open, 'The booking page is open');
    }

    #[Then('j\'ai un lien public, à un code aléatoire')]
    public function iHaveAPublicLink(): void
    {
        Expect::that(1 === preg_match(self::BASE62_OF_22, $this->myPage()->code ?? ''), sprintf('Not 22 random characters of base 62: %s', $this->myPage()->code ?? 'none'));
        Expect::same($this->application->lastBookingCode(), $this->myPage()->code);
    }

    #[Then('je n\'ai pas encore de lien public')]
    public function iHaveNoPublicLinkYet(): void
    {
        Expect::same(null, $this->myPage()->code);
    }

    #[Then('mon agenda est connecté')]
    public function myCalendarIsConnected(): void
    {
        Expect::that($this->myPage()->calendarConnected, 'The calendar is not connected');
    }

    #[Then('mon agenda n\'est pas connecté')]
    public function myCalendarIsNotConnected(): void
    {
        Expect::that(!$this->myPage()->calendarConnected, 'The calendar is connected');
    }

    #[Then('mes réglages sont gardés')]
    public function mySettingsAreKept(): void
    {
        $page = $this->myPage();
        Expect::same([['weekday' => 'monday', 'from' => '14:00', 'to' => '17:00']], $page->windows);
        Expect::same(['call', 'videoCall'], $page->channels);
        Expect::that(null !== $page->code, 'The link is lost');
    }

    #[Then('mon lien public est le même qu\'avant')]
    public function myPublicLinkIsTheSame(): void
    {
        Expect::same($this->formerCode, $this->myPage()->code);
    }

    #[When('on ouvre mon lien public')]
    public function someoneOpensMyPublicLink(): void
    {
        $this->opened = $this->application->consultPublicBookingPage(new ConsultPublicBookingPage($this->myPage()->code ?? '', $this->application->now()));
    }

    #[When('on ouvre le lien public :code')]
    public function someoneOpensThePublicLink(string $code): void
    {
        $this->opened = $this->application->consultPublicBookingPage(new ConsultPublicBookingPage($code, $this->application->now()));
    }

    #[Then('il propose mes créneaux')]
    #[Then('le nouveau propose mes créneaux')]
    public function myPublicLinkOffersMySlots(): void
    {
        $this->someoneOpensMyPublicLink();
        Expect::that(true === $this->opened?->open, 'The public link offers nothing');
    }

    #[Then('il ne propose aucun créneau')]
    #[Then('mon lien public ne propose plus aucun créneau')]
    public function myPublicLinkOffersNothing(): void
    {
        $this->someoneOpensMyPublicLink();
        Expect::that(false === $this->opened?->open, 'The public link still offers slots');
    }

    #[Then('ses premiers créneaux sont :')]
    public function itsFirstSlotsAre(TableNode $table): void
    {
        $expected = array_map(static fn (array $row): string => $row[0], $table->getRows());
        Expect::same($expected, array_slice($this->slotsOffered(), 0, count($expected)));
    }

    #[Then('/^aucun créneau n\'est le '.self::FIRST_DAY.'$/')]
    public function noSlotIsOn(string $first): void
    {
        $onThatDay = array_filter($this->slotsOffered(), static fn (string $slot): bool => str_contains($slot, $first));
        Expect::same([], array_values($onThatDay));
    }

    #[Then('/^le dernier créneau est le (?<slot>\w+ \d{2}\/\d{2}\/\d{4} à \d{1,2}h\d{2})$/u')]
    public function theLastSlotIs(string $slot): void
    {
        $offered = $this->slotsOffered();
        Expect::same(str_replace([' à ', 'h'], [' ', ':'], $slot), end($offered));
    }

    #[Then('/^le créneau du '.self::FIRST_DAY.' à (?<time>\d{1,2}h\d{2}) n\'est pas libre$/u')]
    public function theSlotIsNotFree(string $first, string $time): void
    {
        $slot = sprintf('%s %s', $first, self::time($time));
        Expect::same([], array_values(array_filter($this->slotsOffered(), static fn (string $offered): bool => str_ends_with($offered, $slot))));
    }

    #[When('on ouvre l\'ancien lien')]
    public function someoneOpensTheFormerLink(): void
    {
        $this->someoneOpensThePublicLink($this->formerCode ?? '');
    }

    #[Then('il ne montre rien, sans dire pourquoi')]
    public function itShowsNothingWithoutSayingWhy(): void
    {
        Expect::same(get_object_vars(PublicBookingPage::unavailable()), null === $this->opened ? null : get_object_vars($this->opened));
    }

    #[Then('il propose la visio seulement')]
    public function itOffersTheVideoCallOnly(): void
    {
        Expect::same(['videoCall'], $this->opened?->channels);
    }

    #[Then('l\'ancien lien ne mène plus à rien')]
    public function theFormerLinkLeadsNowhere(): void
    {
        $this->someoneOpensTheFormerLink();
        $this->itShowsNothingWithoutSayingWhy();
    }

    /**
     * @return list<string> each slot written as the scenarios do, `mardi 24/11/2026 09:00`, on the clock of the host
     */
    private function slotsOffered(): array
    {
        $weekdays = array_flip(self::WEEKDAYS);
        $slots = array_merge(...array_map(static fn (array $day): array => $day['slots'], $this->opened->days ?? []));

        return array_map(static fn (string $slot): string => sprintf('%s %s', $weekdays[strtolower(new \DateTimeImmutable($slot)->format('l'))], new \DateTimeImmutable($slot)->format('d/m/Y H:i')), $slots);
    }

    /**
     * @param list<array{weekday: string, from: string, to: string}> $windows
     */
    private function setWindows(array $windows, string $timeZone): void
    {
        $this->application->dispatch(new SetWeeklyWindows($this->me(), $windows, $timeZone, $this->application->now()));
    }

    private function removePeriod(string $id): void
    {
        $this->application->dispatch(new RemoveInactivePeriod($this->me(), $id, $this->application->now()));
    }

    private function offer(string ...$channels): void
    {
        $this->application->dispatch(new OfferChannels($this->me(), array_values($channels), $this->application->now()));
    }

    private function open(): void
    {
        $this->application->dispatch(new OpenBooking($this->me(), $this->application->newBookingCode(), $this->application->now()));
    }

    private function myPage(): MyBookingPage
    {
        return $this->application->consultMyBookingPage(new ConsultMyBookingPage($this->me(), $this->application->readerClock()->getName(), $this->application->now()))
            ?? throw new \LogicException(sprintf('My booking page was refused: %s', $this->application->refusal()?->getMessage()));
    }

    private function me(): string
    {
        return $this->application->signedIn();
    }

    private static function time(string $french): string
    {
        return sprintf('%05s', str_replace('h', ':', $french));
    }

    private static function day(string $french): string
    {
        return (\DateTimeImmutable::createFromFormat('!d/m/Y', $french) ?: throw new \LogicException(sprintf('Not a day: %s', $french)))->format('Y-m-d');
    }
}
