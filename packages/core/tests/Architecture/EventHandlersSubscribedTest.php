<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Tests\Core\Acceptance\Application;

/**
 * The scenarios check a gesture's side effects in the other contexts. So every core event handler and every policy
 * must react in them, as they do in the API.
 */
class EventHandlersSubscribedTest extends TestCase
{
    public function testEveryEventHandlerReactsInTheScenarios(): void
    {
        $this->assertSame(EventHandlersOfTheCore::all(), $this->subscribed(static fn (string $subscriber): bool => !is_a($subscriber, Policy::class, true)));
    }

    public function testEveryPolicyReactsInTheScenarios(): void
    {
        $this->assertSame(PoliciesOfTheCore::all(), $this->subscribed(static fn (string $subscriber): bool => is_a($subscriber, Policy::class, true)));
    }

    /**
     * @param \Closure(string): bool $kind
     *
     * @return list<string>
     */
    private function subscribed(\Closure $kind): array
    {
        $subscribed = array_values(array_filter(new Application()->events->subscribers(), $kind));
        sort($subscribed);

        return $subscribed;
    }
}
