<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\BookingSchedule;
use Prospecter\Core\Booking\Application\MyBookingPage;
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
use Prospecter\Core\Booking\Domain\Policy\BookingSuspension;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A booking page as its item keeps it (ADR 0048, 0148): its windows by day with their HH:MM times, the windows' time
 * zone, its inactive periods by their days, its channels, whether it is open, and its link's code.
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
            'host' => Typed::object($state['host'], MemberId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'windows' => array_map(static fn (Window $window): array => ['weekday' => $window->weekday->value, 'from' => $window->from->text(), 'to' => $window->to->text()], Typed::object($state['windows'], WeeklyWindows::class)->windows),
            'timeZone' => Typed::objectOrNull($state['timeZone'], HostTimeZone::class)?->name,
            'inactivePeriods' => array_map(static fn (InactivePeriod $period): array => ['id' => $period->id->value, 'firstDay' => $period->firstDay, 'lastDay' => $period->lastDay], Typed::objects($state['inactivePeriods'], InactivePeriod::class)),
            'channels' => array_map(static fn (Channel $channel): string => $channel->value, Typed::object($state['channels'], OfferedChannels::class)->channels),
            'open' => Typed::bool($state['open']),
            'code' => Typed::objectOrNull($state['code'], BookingCode::class)?->value,
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the page from its item's untyped document.
     */
    public static function page(mixed $document): BookingPage
    {
        $document = Typed::document($document);
        $periods = array_map(self::period(...), self::periodsOf($document));
        $timeZone = Typed::stringOrNull($document['timeZone']);
        $code = Typed::stringOrNull($document['code']);

        return Hydrator::restore(BookingPage::class, [
            'host' => new MemberId(Typed::string($document['host'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'windows' => new WeeklyWindows(...array_map(self::window(...), self::windowsOf($document))),
            'timeZone' => null === $timeZone ? null : new HostTimeZone($timeZone),
            'inactivePeriods' => array_combine(array_map(static fn (InactivePeriod $period): string => $period->id->value, $periods), $periods),
            'channels' => new OfferedChannels(...array_map(Channel::from(...), Typed::strings($document['channels']))),
            'open' => Typed::bool($document['open']),
            'code' => null === $code ? null : new BookingCode($code),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
        ]);
    }

    /**
     * The host's read model, without restoring the page (ADR 0088). It leaves out the periods that ended before the
     * reading's day, and lists the others soonest first. Its item's document is untyped.
     */
    public static function myPage(mixed $document, string $today, bool $calendarConnected): MyBookingPage
    {
        $document = Typed::document($document);
        $periods = array_values(array_filter(self::periodsOf($document), static fn (array $period): bool => !self::period($period)->hasEndedBefore($today)));
        usort($periods, static fn (array $one, array $other): int => [$one['firstDay'], $one['id']] <=> [$other['firstDay'], $other['id']]);
        $open = Typed::bool($document['open']);
        $code = Typed::stringOrNull($document['code']);

        return new MyBookingPage(
            $open,
            self::windowsOf($document),
            Typed::stringOrNull($document['timeZone']),
            $periods,
            Typed::strings($document['channels']),
            $code,
            $calendarConnected,
            self::intents($open, null !== $code),
            BookingSuspension::isSuspended($open, $calendarConnected),
        );
    }

    public static function none(bool $calendarConnected): MyBookingPage
    {
        return new MyBookingPage(false, [], null, [], [], null, $calendarConnected, self::intents(false, false), false);
    }

    /**
     * The page as its public link needs it, read from its item's untyped document without restoring it (ADR 0088).
     */
    public static function schedule(mixed $document): BookingSchedule
    {
        $document = Typed::document($document);

        return new BookingSchedule(Typed::string($document['host']), Typed::string($document['accountId']), Typed::bool($document['open']), Typed::strings($document['channels']), self::windowsOf($document), Typed::stringOrNull($document['timeZone']), self::periodsOf($document));
    }

    /**
     * @return list<string>
     */
    private static function intents(bool $open, bool $hasALink): array
    {
        return array_map(static fn (BookingPageIntent $intent): string => $intent->name, BookingPagePossibilities::given($open, $hasALink));
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<array{id: string, firstDay: string, lastDay: string}>
     */
    private static function periodsOf(array $document): array
    {
        return array_map(static fn (array $period): array => ['id' => Typed::string($period['id']), 'firstDay' => Typed::string($period['firstDay']), 'lastDay' => Typed::string($period['lastDay'])], Typed::documents($document['inactivePeriods']));
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<array{weekday: string, from: string, to: string}>
     */
    private static function windowsOf(array $document): array
    {
        return array_map(static fn (array $window): array => ['weekday' => Typed::string($window['weekday']), 'from' => Typed::string($window['from']), 'to' => Typed::string($window['to'])], Typed::documents($document['windows']));
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
