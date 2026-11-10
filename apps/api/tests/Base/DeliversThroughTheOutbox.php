<?php

namespace Tests\App\Base;

/**
 * A test of the delivery outbox (ADR 0098), the tests delivering synchronously otherwise: what a command publishes is
 * left in the outbox of its commit, which no relay publishes here.
 */
trait DeliversThroughTheOutbox
{
    private const string DELIVERY = 'INTEGRATION_EVENTS_DELIVERY';

    private ?string $deliveryOfTheTests = null;

    private function deliverThroughTheOutbox(): void
    {
        $this->deliveryOfTheTests = $_SERVER[self::DELIVERY];
        $_SERVER[self::DELIVERY] = $_ENV[self::DELIVERY] = 'outbox';
        $this->rebuildTheServicesReadingTheDelivery();
    }

    /**
     * The delivery is read when the container builds it: a command of the console runs in the kernel of the test,
     * which the data of the demonstration have used already (a request reboots it).
     */
    private function rebuildTheServicesReadingTheDelivery(): void
    {
        self::$kernel->shutdown();
        self::$kernel->boot();
    }

    /**
     * Called by the tearDown() of the test case.
     */
    private function deliverAsTheTestsDo(): void
    {
        if (null !== $this->deliveryOfTheTests) {
            $_SERVER[self::DELIVERY] = $_ENV[self::DELIVERY] = $this->deliveryOfTheTests;
        }
    }

    /**
     * @return list<array<string, mixed>> the items of outbox of a table, one per commit
     */
    private function outboxOf(string $table): array
    {
        return self::getContainer()->get('app.table.'.$table)->scan('OUTBOX#');
    }
}
