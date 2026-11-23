<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Psr\Clock\ClockInterface;
use Symfony\Component\RateLimiter\LimiterStateInterface;
use Symfony\Component\RateLimiter\Policy\SlidingWindow;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

/**
 * Keeps the counts of Booking's public route limiters in the Booking table (ADR 0153). Every function of the API shares
 * them, because one function's memory is not shared. The key is `RATE_LIMIT#<limiter and what it limits>` /
 * `RATE_LIMIT`. The item holds the limiter's serialized state (a sliding window, the policy of these limiters).
 * `expiresAt` is the epoch at which the table forgets it.
 *
 * The storage writes outside any unit of work. A count is not a fact of an action, and a lost count lets one more
 * request through.
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
        $state = Typed::stringOrNull($this->bookingTable->item(BookingKeys::rateLimit($limiterStateId), BookingKeys::RATE_LIMIT)['state'] ?? null);
        $restored = null === $state ? null : unserialize($state, ['allowed_classes' => [SlidingWindow::class]]);

        return $restored instanceof LimiterStateInterface ? $restored : null;
    }

    public function delete(string $limiterStateId): void
    {
        $this->bookingTable->batch([], [[BookingKeys::rateLimit($limiterStateId), BookingKeys::RATE_LIMIT]]);
    }
}
