<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\PublishAccountOpened;

use Prospecter\Core\Accounts\Contract\Event as Published;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

final readonly class PublishAccountOpenedHandler implements EventHandler
{
    public function __construct(private Events $events)
    {
    }

    public function __invoke(AccountOpened $event): void
    {
        $this->events->publish(new Published\AccountOpened(
            (string) $event->accountId,
            (string) $event->by,
            (string) $event->founderProfile,
            $event->occurredAt,
        ));
    }
}
