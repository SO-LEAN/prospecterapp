<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Persistence\DynamoUnitOfWork;

/**
 * Delivers an event after the command. The commit writes the event in the outbox with what the command changes, all
 * or nothing. The relay publishes it on the bus once it is written. No reaction runs within the command.
 */
final readonly class OutboxDelivery implements Delivery
{
    public function __construct(private CommitOutbox $outbox, private DynamoUnitOfWork $unitOfWork)
    {
    }

    public function deliver(TransportedEvent $event): void
    {
        $this->outbox->add($event);
        $this->unitOfWork->sendTheOutboxOutsideAUnit();
    }
}
