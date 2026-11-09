<?php

namespace Tests\App\Architecture;

use App\Shared\DependencyInjection\SubscriptionsPass;
use App\Shared\Messaging\DomainEventTypes;
use App\Shared\Messaging\EventTypes;
use App\Shared\Messaging\IntegrationEventTypes;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * The reactions live in one list, `config/subscriptions.json`, read by Symfony and by the infrastructure: an event
 * handler of the core is either a translator publishing a domain event to the other contexts, or a subscription
 * of that list; a domain event never leaves its context (ADR 0046).
 */
class ReactionsAreSubscribedTest extends TestCase
{
    private const string INTERNAL = '.internal';

    public function testEveryEventHandlerIsATranslatorOrASubscription(): void
    {
        $subscribed = array_column($this->subscriptions(), 'handler');
        $undeclared = array_filter(
            $this->eventHandlersOfTheCore(),
            static fn (string $handler): bool => !preg_match(SubscriptionsPass::TRANSLATOR, $handler) && !in_array($handler, $subscribed, true),
        );

        $this->assertSame([], array_values($undeclared), 'reactions missing from config/subscriptions.json');
    }

    public function testASubscriptionNamesAKnownReaction(): void
    {
        $reactions = array_filter($this->eventHandlersOfTheCore(), static fn (string $handler): bool => !preg_match(SubscriptionsPass::TRANSLATOR, $handler));
        $unknown = array_diff(array_column($this->subscriptions(), 'handler'), $reactions);

        $this->assertSame([], array_values($unknown));
    }

    public function testASubscriptionIsNamedAfterTheContextOfItsReaction(): void
    {
        foreach ($this->subscriptions() as $name => $subscription) {
            $this->assertSame(self::contextOf($subscription['handler']), $subscription['context'], $name);
            $this->assertStringStartsWith($subscription['context'].'-', $name);
        }
    }

    public function testADomainEventIsSubscribedToInItsContextOnly(): void
    {
        foreach ($this->subscriptions() as $name => $subscription) {
            foreach ($subscription['sources'] as $source) {
                if (str_ends_with($source, self::INTERNAL)) {
                    $this->assertSame('prospecter.'.$subscription['context'].self::INTERNAL, $source, $name);
                }
            }
        }
    }

    public function testASubscriptionListensToListedEvents(): void
    {
        $types = [new DomainEventTypes(), new IntegrationEventTypes()];

        foreach ($this->subscriptions() as $name => $subscription) {
            foreach ($subscription['sources'] as $source) {
                foreach ($subscription['events'] as $detailType) {
                    $known = array_any($types, static fn (EventTypes $list): bool => null !== $list->classOf($source, $detailType));
                    $this->assertTrue($known, sprintf('%s: %s of %s is listed in no event types', $name, $detailType, $source));
                }
            }
        }
    }

    /**
     * `Prospecter\Core\PersonalData\…` belongs to the context `personal-data`.
     */
    private static function contextOf(string $class): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', explode('\\', $class)[2]));
    }

    /**
     * @return array<string, array{context: string, sources: list<string>, events: list<string>, handler: string, inbox: bool}>
     */
    private function subscriptions(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/config/subscriptions.json'), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    private function eventHandlersOfTheCore(): array
    {
        $root = dirname(__DIR__, 2).'/vendor/prospecter/core/src/';
        $handlers = [];

        foreach (glob($root.'*/Application/*/*Handler.php') ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, EventHandler::class, true)) {
                $handlers[] = $class;
            }
        }

        return $handlers;
    }
}
