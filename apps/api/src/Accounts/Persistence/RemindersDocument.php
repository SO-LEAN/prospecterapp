<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Typed;
use Prospecter\Core\Accounts\Domain\Model\ReminderDelay;
use Prospecter\Core\Accounts\Domain\Model\Reminders;

/**
 * A member's reminders in the member's document. A member written before reminders existed gets the default ones.
 */
final class RemindersDocument
{
    /**
     * @return array{enabled: bool, minutesBefore: int}
     */
    public static function of(Reminders $reminders): array
    {
        return ['enabled' => $reminders->enabled, 'minutesBefore' => $reminders->delay->value];
    }

    public static function restore(mixed $document): Reminders
    {
        if (null === $document) {
            return Reminders::byDefault();
        }

        $document = Typed::document($document);

        return new Reminders(Typed::bool($document['enabled']), ReminderDelay::from(self::minutesOf($document['minutesBefore'])));
    }

    /**
     * Reads the minutes before as a number. DynamoDB may give them back as a numeric string.
     */
    private static function minutesOf(mixed $minutes): int
    {
        return is_int($minutes) ? $minutes : (int) Typed::string($minutes);
    }
}
