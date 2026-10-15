<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\EventHandler;
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

        $this->assertSame($this->eventHandlersOfTheCore(), $subscribed);
    }

    /**
     * @return list<string>
     */
    private function eventHandlersOfTheCore(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $handlers = [];

        foreach (glob($root.'*/Application/*/*Handler.php') ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, EventHandler::class, true)) {
                $handlers[] = $class;
            }
        }

        sort($handlers);

        return $handlers;
    }
}
