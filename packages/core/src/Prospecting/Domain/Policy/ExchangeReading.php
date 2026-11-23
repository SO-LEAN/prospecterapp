<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * What a prospector reads of an exchange (ADR 0078). Every prospector of the account reads the text. The text may hold
 * personal data, so it is flagged for a prospector without the right to manage them. Only a prospector with that right
 * reads the attached files.
 */
final class ExchangeReading
{
    #[\NoDiscard]
    public static function isTextFlagged(bool $hasText, Prospector $reader): bool
    {
        return $hasText && !$reader->managesPersonalData;
    }

    #[\NoDiscard]
    public static function readsTheAttachments(Prospector $reader): bool
    {
        return $reader->managesPersonalData;
    }
}
