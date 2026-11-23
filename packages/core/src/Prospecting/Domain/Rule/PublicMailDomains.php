<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Rule;

use Prospecter\Core\SharedKernel\Domain\Email;

/**
 * The domains of the mailboxes that anyone can open. A person's email there says nothing about the organization they
 * work for. So Prospecter proposes no organization to attach them to (ADR 0146).
 */
final class PublicMailDomains
{
    private const array DOMAINS = [
        'gmail.com', 'googlemail.com', 'outlook.com', 'outlook.fr', 'hotmail.com', 'hotmail.fr', 'live.com', 'live.fr',
        'msn.com', 'yahoo.com', 'yahoo.fr', 'icloud.com', 'me.com', 'mac.com', 'aol.com', 'gmx.com', 'gmx.fr',
        'protonmail.com', 'proton.me', 'orange.fr', 'wanadoo.fr', 'free.fr', 'sfr.fr', 'neuf.fr', 'laposte.net',
        'bbox.fr', 'numericable.fr', 'club-internet.fr', 'aliceadsl.fr', 'yandex.com', 'mail.com', 'tutanota.com',
    ];

    #[\NoDiscard]
    public static function includes(Email $email): bool
    {
        return in_array($email->domain(), self::DOMAINS, true);
    }
}
