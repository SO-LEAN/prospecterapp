<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\TwoWindowsOnADay;

/**
 * The windows of the week of the host, one a day at most (several a day are out of scope, #177), Monday first.
 */
final readonly class WeeklyWindows
{
    /**
     * @var list<Window>
     */
    public array $windows;

    public function __construct(Window ...$windows)
    {
        $weekdays = array_map(static fn (Window $window): string => $window->weekday->value, $windows);
        if (count(array_unique($weekdays)) !== count($weekdays)) {
            throw TwoWindowsOnADay::refused();
        }
        usort($windows, static fn (Window $one, Window $other): int => array_search($one->weekday, Weekday::cases(), true) <=> array_search($other->weekday, Weekday::cases(), true));
        $this->windows = $windows;
    }
}
