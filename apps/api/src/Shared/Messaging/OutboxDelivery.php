<?php

namespace App\Shared\Messaging;

use App\Shared\Persistence\DynamoUnitOfWork;

/**
 * Delivers an event after the command: written in the outbox of its commit, with what the command changes, all or
 * nothing; the relay publishes it on the bus once written. No reaction runs within the command.
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
