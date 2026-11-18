<?php

namespace Tests\App\Booking;

use App\Booking\Persistence\DynamoRateLimiterStorage;
use Symfony\Component\RateLimiter\Policy\SlidingWindow;
use Tests\App\Base\ApiTestCase;

/**
 * The counts of a limiter of the public routes kept in the table of Booking, as every function of the api reads them
 * (ADR 0153).
 */
class DynamoRateLimiterStorageTest extends ApiTestCase
{
    private const string LIMITED = 'public_booking_link-AbCdEfGhIjKlMnOpQrStUv';
    private const int A_MINUTE = 60;

    public function testTheCountsOfALimiterAreReadAsTheyWereSaved(): void
    {
        $window = new SlidingWindow(self::LIMITED, self::A_MINUTE);
        $window->add(3);

        $this->storage()->save($window);
        $read = $this->storage()->fetch(self::LIMITED);

        $this->assertInstanceOf(SlidingWindow::class, $read);
        $this->assertSame(self::LIMITED, $read->getId());
        $this->assertSame($window->getHitCount(), $read->getHitCount());
    }

    public function testTheCountsOfALimiterDeletedAreNoMore(): void
    {
        $this->storage()->save(new SlidingWindow(self::LIMITED, self::A_MINUTE));

        $this->storage()->delete(self::LIMITED);

        $this->assertNull($this->storage()->fetch(self::LIMITED));
    }

    public function testALimiterNeverSavedHasNoCounts(): void
    {
        $this->assertNull($this->storage()->fetch(self::LIMITED));
    }

    private function storage(): DynamoRateLimiterStorage
    {
        return self::getContainer()->get(DynamoRateLimiterStorage::class);
    }
}
