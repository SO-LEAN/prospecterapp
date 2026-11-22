<?php

declare(strict_types=1);

namespace Tests\App\Shared\Messaging;

use App\Shared\Messaging\TransportedEvent;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TransportedEventTest extends TestCase
{
    private const array DETAIL = ['eventId' => 'e1', 'occurredAt' => '2026-11-22T10:00:00+00:00', 'correlationId' => 'c1', 'causationId' => null, 'depth' => 0, 'payload' => ['accountId' => 'a1']];

    public function testAnEntryOfOutboxWhoseDepthIsNoIntegerIsRefused(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        TransportedEvent::fromOutbox(['source' => 'prospecter.accounts', 'detailType' => 'AccountOpened.v1', 'detail' => [...self::DETAIL, 'depth' => '0']]);
    }
}
