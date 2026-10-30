<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * Where the contact of an exchange stands: still a contact of the organization reading it, removed from it but
 * known, or forgotten, its card erased (ADR 0079).
 */
enum ContactPresence: string
{
    case Present = 'present';
    case Removed = 'removed';
    case Forgotten = 'forgotten';

    /**
     * The contact still known, or forgotten; none for an exchange with the organization itself.
     */
    public static function ofTheContact(bool $hasAContact, bool $forgotten): ?self
    {
        if (!$hasAContact) {
            return null;
        }

        return $forgotten ? self::Forgotten : self::Present;
    }
}
