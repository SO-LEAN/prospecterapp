<?php

declare(strict_types=1);

namespace Fixtures\Cache;

use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;

final readonly class SendInvitationHandler implements CommandHandler
{
    public function __invoke(SendInvitation $command): void
    {
    }
}
