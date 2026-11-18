<?php

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use Psr\Clock\ClockInterface;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\Policy\SlidingWindow;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

/**
 * The counts of the limiters of the public routes of Booking in its table (ADR 0153), shared by every function of
 * the api, as the memory of one is not: `RATE_LIMIT#<limiter and what it limits>` / `RATE_LIMIT`, the state of the
 * limiter serialized (a sliding window, the policy of these limiters), `expiresAt` the epoch the table forgets it at. Written apart from any unit of work: a count is no
 * fact of a gesture, and losing one lets a request more through.
 */
final readonly class DynamoRateLimiterStorage implements StorageInterface
{
    public function __construct(private Table $bookingTable, private ClockInterface $clock)
    {
    }

    public function save(LimiterStateInterface $limiterState): void
    {
        $this->bookingTable->batch([[
            'PK' => BookingKeys::rateLimit($limiterState->getId()),
            'SK' => BookingKeys::RATE_LIMIT,
            'state' => serialize($limiterState),
            'expiresAt' => $this->clock->now()->getTimestamp() + ($limiterState->getExpirationTime() ?? 0),
        ]]);
    }

    public function fetch(string $limiterStateId): ?LimiterStateInterface
    {
        $state = $this->bookingTable->item(BookingKeys::rateLimit($limiterStateId), BookingKeys::RATE_LIMIT)['state'] ?? null;
        $restored = null === $state ? null : unserialize($state, ['allowed_classes' => [SlidingWindow::class]]);

        return $restored instanceof LimiterStateInterface ? $restored : null;
    }

    public function delete(string $limiterStateId): void
    {
        $this->bookingTable->batch([], [[BookingKeys::rateLimit($limiterStateId), BookingKeys::RATE_LIMIT]]);
    }
}
