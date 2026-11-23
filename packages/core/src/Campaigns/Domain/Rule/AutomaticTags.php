<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Rule;

/**
 * The names of the tags that Prospecter sets by itself and nobody puts by hand (ADR 0138, 0142). They tell how a
 * contact is reached and where an organization is: its big city, its department and its region.
 */
final readonly class AutomaticTags
{
    private const array CHANNELS = ['EMAIL', 'PHONE', 'SMS', 'LINKEDIN'];
    private const string DEPARTMENT = '/^(\d{2}|97\d|2[AB])$/';

    #[\NoDiscard]
    public static function reserve(string $name): bool
    {
        return in_array($name, self::CHANNELS, true)
            || 1 === preg_match(self::DEPARTMENT, $name)
            || GeographicTags::reserve($name);
    }
}
