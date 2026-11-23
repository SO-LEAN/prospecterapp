<?php

declare(strict_types=1);

namespace Fixtures\CacheOfCommands;

use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;

final readonly class SendInvitationHandler implements EventHandler
{
    public function __invoke(SendInvitation $invitation): void
    {
    }
}
