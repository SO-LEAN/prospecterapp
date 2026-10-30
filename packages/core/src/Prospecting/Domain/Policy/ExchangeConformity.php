<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

/**
 * Its contact forgotten, an exchange may still hold their personal data: in its text until it is declared
 * conform, in its files until they are removed (ADR 0079). Asked by the exchange and by its reading (ADR 0088).
 */
final class ExchangeConformity
{
    public static function awaitsBeingMadeConform(bool $contactForgotten, bool $declaredConform): bool
    {
        return $contactForgotten && !$declaredConform;
    }

    public static function isTextToMakeConform(bool $hasText, bool $contactForgotten, bool $declaredConform): bool
    {
        return $hasText && self::awaitsBeingMadeConform($contactForgotten, $declaredConform);
    }

    /**
     * A file is made conform by removing it.
     */
    public static function areAttachmentsToMakeConform(bool $contactForgotten): bool
    {
        return $contactForgotten;
    }

    public static function isToMakeConform(bool $contactForgotten, bool $declaredConform, bool $hasAttachments): bool
    {
        return self::awaitsBeingMadeConform($contactForgotten, $declaredConform) || (self::areAttachmentsToMakeConform($contactForgotten) && $hasAttachments);
    }
}
