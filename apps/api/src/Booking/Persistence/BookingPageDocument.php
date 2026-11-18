<?php

namespace App\Booking\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingPageIntent;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\InactivePeriodId;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;
use Prospecter\Core\Booking\Domain\Model\WallClockTime;
use Prospecter\Core\Booking\Domain\Model\Weekday;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\Booking\Domain\Policy\BookingPagePossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A booking page as its item keeps it (ADR 0048, 0148): its windows by their days and their times HH:MM, the time
 * zone they are set in, its inactive periods by their days, its channels, whether it is open, the code of its link.
 */
final class BookingPageDocument
{
    private const string FIELD = 'times';

    /**
     * @return array<string, mixed>
     */
    public static function of(BookingPage $page): array
    {
        $state = Hydrator::stateOf($page);

        return [
            'host' => (string) $state['host'],
            'accountId' => (string) $state['accountId'],
            'windows' => array_map(static fn (Window $window): array => ['weekday' => $window->weekday->value, 'from' => $window->from->text(), 'to' => $window->to->text()], $state['windows']->windows),
            'timeZone' => $state['timeZone']?->name,
            'inactivePeriods' => array_values(array_map(static fn (InactivePeriod $period): array => ['id' => $period->id->value, 'firstDay' => $period->firstDay, 'lastDay' => $period->lastDay], $state['inactivePeriods'])),
            'channels' => array_map(static fn (Channel $channel): string => $channel->value, $state['channels']->channels),
            'open' => $state['open'],
            'code' => $state['code']?->value,
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function page(array $document): BookingPage
    {
        $periods = array_map(self::period(...), $document['inactivePeriods']);

        return Hydrator::restore(BookingPage::class, [
            'host' => new MemberId($document['host']),
            'accountId' => new AccountId($document['accountId']),
            'windows' => new WeeklyWindows(...array_map(self::window(...), $document['windows'])),
            'timeZone' => null === $document['timeZone'] ? null : new HostTimeZone($document['timeZone']),
            'inactivePeriods' => array_combine(array_map(static fn (InactivePeriod $period): string => $period->id->value, $periods), $periods),
            'channels' => new OfferedChannels(...array_map(Channel::from(...), $document['channels'])),
            'open' => $document['open'],
            'code' => null === $document['code'] ? null : new BookingCode($document['code']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * The read model of its host, without restoring the page (ADR 0088): the periods ended before the day of the
     * reading left out, the soonest first.
     *
     * @param array<string, mixed> $document
     */
    public static function myPage(array $document, string $today, bool $calendarConnected): MyBookingPage
    {
        $periods = array_values(array_filter($document['inactivePeriods'], static fn (array $period): bool => !self::period($period)->hasEndedBefore($today)));
        usort($periods, static fn (array $one, array $other): int => [$one['firstDay'], $one['id']] <=> [$other['firstDay'], $other['id']]);

        return new MyBookingPage(
            $document['open'],
            $document['windows'],
            $document['timeZone'],
            $periods,
            $document['channels'],
            $document['code'],
            $calendarConnected,
            self::intents($document['open'], null !== $document['code']),
        );
    }

    public static function none(bool $calendarConnected): MyBookingPage
    {
        return new MyBookingPage(false, [], null, [], [], null, $calendarConnected, self::intents(false, false));
    }

    /**
     * The page as anyone holding its link reads it.
     *
     * @param array<string, mixed> $document
     */
    public static function publicPage(array $document): PublicBookingPage
    {
        return new PublicBookingPage($document['open'], $document['channels']);
    }

    /**
     * @return list<string>
     */
    private static function intents(bool $open, bool $hasALink): array
    {
        return array_map(static fn (BookingPageIntent $intent): string => $intent->name, BookingPagePossibilities::given($open, $hasALink));
    }

    /**
     * @param array{id: string, firstDay: string, lastDay: string} $period
     */
    private static function period(array $period): InactivePeriod
    {
        return new InactivePeriod(new InactivePeriodId($period['id']), $period['firstDay'], $period['lastDay']);
    }

    /**
     * @param array{weekday: string, from: string, to: string} $window
     */
    private static function window(array $window): Window
    {
        return new Window(Weekday::from($window['weekday']), WallClockTime::at($window['from'], self::FIELD), WallClockTime::at($window['to'], self::FIELD));
    }
}
