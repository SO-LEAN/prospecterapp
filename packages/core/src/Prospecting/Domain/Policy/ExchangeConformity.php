<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

/**
 * Once its contact is forgotten, an exchange may still hold their personal data. Its text holds them until a member
 * declares it redacted. Its files hold them until a member removes them (ADR 0079). The exchange and its reading both
 * ask this policy (ADR 0088).
 */
final class ExchangeConformity
{
    #[\NoDiscard]
    public static function awaitsBeingMadeConform(bool $contactForgotten, bool $declaredConform): bool
    {
        return $contactForgotten && !$declaredConform;
    }

    #[\NoDiscard]
    public static function isTextToMakeConform(bool $hasText, bool $contactForgotten, bool $declaredConform): bool
    {
        return $hasText && self::awaitsBeingMadeConform($contactForgotten, $declaredConform);
    }

    /**
     * A member redacts a file by removing it.
     */
    #[\NoDiscard]
    public static function areAttachmentsToMakeConform(bool $contactForgotten): bool
    {
        return $contactForgotten;
    }

    #[\NoDiscard]
    public static function isToMakeConform(bool $contactForgotten, bool $declaredConform, bool $hasAttachments): bool
    {
        return self::awaitsBeingMadeConform($contactForgotten, $declaredConform) || (self::areAttachmentsToMakeConform($contactForgotten) && $hasAttachments);
    }
}
