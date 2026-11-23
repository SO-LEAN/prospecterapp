<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Tests\Core\Acceptance\Application;

/**
 * The scenarios check a gesture's side effects in the other contexts. So every core event handler must react in
 * them, as it does in the API.
 */
class EventHandlersSubscribedTest extends TestCase
{
    public function testEveryEventHandlerReactsInTheScenarios(): void
    {
        $subscribed = new Application()->events->subscribers();
        sort($subscribed);

        $this->assertSame(EventHandlersOfTheCore::all(), $subscribed);
    }
}
