<?php

declare(strict_types=1);

namespace App\Accounts\Persistence;

use App\Shared\Typed;
use Prospecter\Core\Accounts\Domain\Model\ReminderDelay;
use Prospecter\Core\Accounts\Domain\Model\Reminders;

/**
 * The reminders of a member in the document of the member; a member written before they had reminders has those by
 * default.
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
     * The minutes before as a number, or as the string of a number DynamoDB gave back.
     */
    private static function minutesOf(mixed $minutes): int
    {
        return is_int($minutes) ? $minutes : (int) Typed::string($minutes);
    }
}
