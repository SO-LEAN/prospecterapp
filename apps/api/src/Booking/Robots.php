<?php

declare(strict_types=1);

namespace App\Booking;

use App\Booking\Input\BookingInput;

/**
 * Tells a form that a robot filled from a form that a person filled, without a captcha (ADR 0164). A person leaves the
 * hidden trap empty, and takes some seconds to fill the form that the page showed.
 */
final readonly class Robots
{
    private const int SHORTEST_FILLING_SECONDS = 3;

    public static function filled(BookingInput $input, \DateTimeImmutable $at): bool
    {
        return '' !== trim($input->website ?? '') || null === $input->formShownAt || self::secondsSince($input->formShownAt, $at) < self::SHORTEST_FILLING_SECONDS;
    }

    private static function secondsSince(\DateTimeImmutable $shownAt, \DateTimeImmutable $at): int
    {
        return $at->getTimestamp() - $shownAt->getTimestamp();
    }
}
