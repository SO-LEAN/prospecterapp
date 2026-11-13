<?php

namespace App\Accounts\Persistence;

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

    /**
     * @param array{enabled: bool, minutesBefore: int|string}|null $document
     */
    public static function restore(?array $document): Reminders
    {
        return null === $document ? Reminders::byDefault() : new Reminders($document['enabled'], ReminderDelay::from((int) $document['minutesBefore']));
    }
}
