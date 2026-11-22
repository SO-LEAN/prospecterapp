<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Tests\Core\Acceptance\Application;

/**
 * The scenarios check the side effects of a gesture in the other contexts: every event handler of the core
 * must react in them, as it does in the API.
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
