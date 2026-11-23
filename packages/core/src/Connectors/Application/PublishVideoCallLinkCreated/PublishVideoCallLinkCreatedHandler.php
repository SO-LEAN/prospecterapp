<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\PublishVideoCallLinkCreated;

use Prospecter\Core\Connectors\Contract\Event as Published;
use Prospecter\Core\Connectors\Domain\Event\VideoCallLinkCreated;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * Tells the other contexts, in the published language, about the link that a member's calendar created for a video
 * call (ADR 0046, 0137).
 */
final readonly class PublishVideoCallLinkCreatedHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(VideoCallLinkCreated $event): void
    {
        $this->events->publish(new Published\VideoCallLinkCreated(
            (string) $event->accountId,
            (string) $event->by,
            (string) $event->exchange,
            $event->link,
            $event->occurredAt,
        ));
    }
}
