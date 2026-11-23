<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where an exchange's contact stands. They are still a contact of the organization reading the exchange, removed
 * from it but known, or forgotten with their card erased (ADR 0079).
 */
enum ContactPresence: string
{
    case Present = 'present';
    case Removed = 'removed';
    case Forgotten = 'forgotten';

    /**
     * The contact is still known, or forgotten. An exchange with the organization itself has none.
     */
    public static function ofTheContact(bool $hasAContact, bool $forgotten): ?self
    {
        if (!$hasAContact) {
            return null;
        }

        return $forgotten ? self::Forgotten : self::Present;
    }

    /**
     * In an organization's timeline, a known contact may have left the organization since.
     */
    public static function inTheOrganization(bool $hasAContact, bool $forgotten, bool $stillItsContact): ?self
    {
        $presence = self::ofTheContact($hasAContact, $forgotten);

        return self::Present === $presence && !$stillItsContact ? self::Removed : $presence;
    }
}
