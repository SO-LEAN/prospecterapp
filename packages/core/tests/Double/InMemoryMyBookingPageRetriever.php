<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Application\MyBookingPage;
use Prospecter\Core\Booking\Application\Port\MyBookingPageRetriever;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPageIntent;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\Booking\Domain\Policy\BookingPagePossibilities;
use Prospecter\Core\Booking\Domain\Policy\BookingSuspension;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The DynamoDB retriever in the scenarios: the page the repository saved, read without its getters, closed and empty
 * for a host who never set it; the inactive periods not ended, the soonest first.
 */
final readonly class InMemoryMyBookingPageRetriever implements MyBookingPageRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(MemberId $host, string $today, bool $calendarConnected): MyBookingPage
    {
        $page = $this->database->bookingPage($host->value);
        if (null === $page) {
            return new MyBookingPage(false, [], null, [], [], null, $calendarConnected, self::intents(false, null), false);
        }
        $state = StateOf::the($page);
        /** @var WeeklyWindows $windows */
        $windows = $state->object('windows', WeeklyWindows::class);
        /** @var ?HostTimeZone $timeZone */
        $timeZone = $state->objectOrNull('timeZone', HostTimeZone::class);
        /** @var array<string, InactivePeriod> $periods */
        $periods = $state->value('inactivePeriods');
        /** @var OfferedChannels $channels */
        $channels = $state->object('channels', OfferedChannels::class);
        /** @var ?BookingCode $code */
        $code = $state->objectOrNull('code', BookingCode::class);
        $open = $state->bool('open');

        return new MyBookingPage(
            $open,
            array_map(static fn (Window $window): array => ['weekday' => $window->weekday->value, 'from' => $window->from->text(), 'to' => $window->to->text()], $windows->windows),
            $timeZone?->name,
            self::notEndedBefore($today, $periods),
            array_map(static fn (Channel $channel): string => $channel->value, $channels->channels),
            $code?->value,
            $calendarConnected,
            self::intents($open, $code),
            BookingSuspension::isSuspended($open, $calendarConnected),
        );
    }

    /**
     * @param array<string, InactivePeriod> $periods
     *
     * @return list<array{id: string, firstDay: string, lastDay: string}>
     */
    private static function notEndedBefore(string $today, array $periods): array
    {
        $current = array_filter($periods, static fn (InactivePeriod $period): bool => !$period->hasEndedBefore($today));
        usort($current, static fn (InactivePeriod $one, InactivePeriod $other): int => [$one->firstDay, $one->id->value] <=> [$other->firstDay, $other->id->value]);

        return array_map(static fn (InactivePeriod $period): array => ['id' => $period->id->value, 'firstDay' => $period->firstDay, 'lastDay' => $period->lastDay], $current);
    }

    /**
     * @return list<string>
     */
    private static function intents(bool $open, ?BookingCode $code): array
    {
        return array_map(static fn (BookingPageIntent $intent): string => $intent->name, BookingPagePossibilities::given($open, null !== $code));
    }
}
