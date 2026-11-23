<?php

declare(strict_types=1);

namespace Fixtures\Cache;

final readonly class InvitationSender
{
    public function __invoke(): SendInvitation
    {
        return new SendInvitation('account', 'guest@example.com', '0102030405', new \DateTimeImmutable('2026-11-23'));
    }
}
