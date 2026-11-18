<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * A page gets its link at its first opening: before it, there is no link to change.
 */
final class BookingPageWithoutLink extends NotPossibleInItsState
{
    public static function doesNotChangeIt(): self
    {
        return self::because('A booking page without a link does not change it');
    }
}
