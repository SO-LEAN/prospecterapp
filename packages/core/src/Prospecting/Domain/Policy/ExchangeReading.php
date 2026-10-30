<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * What a prospector reads of an exchange (ADR 0078): every prospector of the account reads the text, flagged
 * without the right to manage personal data, for it may hold some; the files joined to it only with that right.
 */
final class ExchangeReading
{
    public static function isTextFlagged(bool $hasText, Prospector $reader): bool
    {
        return $hasText && !$reader->managesPersonalData;
    }

    public static function readsTheAttachments(Prospector $reader): bool
    {
        return $reader->managesPersonalData;
    }
}
