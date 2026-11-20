<?php

namespace App\Booking;

use App\Booking\Input\BookingInput;

/**
 * Tells a form a robot filled from one a person did, without a captcha (ADR 0164): a person leaves the hidden trap
 * empty, and takes some seconds to fill the form the page showed.
 */
final readonly class Robots
{
    private const int SHORTEST_FILLING_SECONDS = 3;

    public static function filled(BookingInput $input, \DateTimeImmutable $at): bool
    {
        return '' !== trim($input->website ?? '') || null === $input->formShownAt || self::secondsSince($input->formShownAt, $at) < self::SHORTEST_FILLING_SECONDS;
    }

    private static function secondsSince(string $shownAt, \DateTimeImmutable $at): int
    {
        return $at->getTimestamp() - new \DateTimeImmutable($shownAt)->getTimestamp();
    }
}
