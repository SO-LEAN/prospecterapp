<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\BookingSchedule;
use Prospecter\Core\Booking\Application\Port\PublicBookingPageRetriever;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\HostTimeZone;
use Prospecter\Core\Booking\Domain\Model\InactivePeriod;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;
use Prospecter\Core\Booking\Domain\Model\WeeklyWindows;
use Prospecter\Core\Booking\Domain\Model\Window;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The DynamoDB retriever in the scenarios: the page the code it has now leads to, read without its getters.
 */
final readonly class InMemoryPublicBookingPageRetriever implements PublicBookingPageRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function byCode(string $code): ?BookingSchedule
    {
        $page = $this->database->bookingPageAt($code);
        if (null === $page) {
            return null;
        }
        $state = StateOf::the($page);
        /** @var MemberId $host */
        $host = $state['host'];
        /** @var AccountId $accountId */
        $accountId = $state['accountId'];
        /** @var OfferedChannels $channels */
        $channels = $state['channels'];
        /** @var WeeklyWindows $windows */
        $windows = $state['windows'];
        /** @var ?HostTimeZone $timeZone */
        $timeZone = $state['timeZone'];
        /** @var array<string, InactivePeriod> $periods */
        $periods = $state['inactivePeriods'];

        return new BookingSchedule(
            $host->value,
            $accountId->value,
            (bool) $state['open'],
            array_map(static fn (Channel $channel): string => $channel->value, $channels->channels),
            array_map(static fn (Window $window): array => ['weekday' => $window->weekday->value, 'from' => $window->from->text(), 'to' => $window->to->text()], $windows->windows),
            $timeZone?->name,
            array_values(array_map(static fn (InactivePeriod $period): array => ['id' => $period->id->value, 'firstDay' => $period->firstDay, 'lastDay' => $period->lastDay], $periods)),
        );
    }
}
