<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\PublishMemberRegistered;

use Prospecter\Core\Accounts\Contract\Event as Published;
use Prospecter\Core\Accounts\Domain\Event\MemberRegistered;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

final readonly class PublishMemberRegisteredHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(MemberRegistered $event): void
    {
        $this->events->publish(new Published\MemberRegistered(
            (string) $event->accountId,
            (string) $event->by,
            (string) $event->memberId,
            (string) $event->profile,
            $event->occurredAt,
        ));
    }
}
