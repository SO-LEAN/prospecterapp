<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Shared\DependencyInjection\SubscriptionsPass;
use App\Shared\Messaging\DomainEventTypes;
use App\Shared\Messaging\EventTypes;
use App\Shared\Messaging\IntegrationEventTypes;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * The reactions live in one list, `config/subscriptions.json`, read by Symfony and by the infrastructure. Every policy
 * of the core is a subscription of that list, which listens only to the events its signature takes (ADR 0203). An
 * event handler of the core is either a translator publishing a domain event to the other contexts, or a subscription
 * of that list, until it becomes a policy. A domain event never leaves its context (ADR 0046).
 */
class ReactionsAreSubscribedTest extends TestCase
{
    private const string INTERNAL = '.internal';

    /** The contexts the API serves, whose handlers `config/services.yaml` loads. */
    private const string SERVED_CONTEXTS = '{Accounts,Booking,Campaigns,Connectors,PersonalData,Prospecting,Reporting}';

    public function testEveryEventHandlerIsATranslatorOrASubscription(): void
    {
        $subscribed = $this->handlersOfTheSubscriptions();
        $undeclared = array_filter(
            $this->eventHandlersOfTheCore(),
            static fn (string $handler): bool => !preg_match(SubscriptionsPass::TRANSLATOR, $handler) && !in_array($handler, $subscribed, true),
        );

        $this->assertSame([], array_values($undeclared), 'reactions missing from config/subscriptions.json');
    }

    public function testEveryPolicyIsSubscribed(): void
    {
        $this->assertSame([], array_values(array_diff($this->policiesOfTheCore(), $this->policiesOfTheSubscriptions())), 'policies missing from config/subscriptions.json');
    }

    public function testASubscriptionNamesAKnownPolicy(): void
    {
        $this->assertSame([], array_values(array_diff($this->policiesOfTheSubscriptions(), $this->policiesOfTheCore())));
    }

    /**
     * Each detail-type a subscription listens to is read as a class that its policy's signature takes.
     */
    public function testASubscriptionListensToTheEventsItsPolicyTakes(): void
    {
        foreach ($this->subscriptions() as $name => $subscription) {
            if (!isset($subscription['policy'])) {
                continue;
            }

            $taken = self::eventsTakenBy($subscription['policy']);

            foreach ($subscription['events'] as $detailType) {
                $read = array_any(self::classesOf($subscription['sources'], $detailType), static fn (string $class): bool => array_any($taken, static fn (string $event): bool => is_a($class, $event, true)));
                $this->assertTrue($read, sprintf('%s: %s is not taken by %s', $name, $detailType, $subscription['policy']));
            }
        }
    }

    public function testASubscriptionNamesAKnownReaction(): void
    {
        $reactions = array_filter($this->eventHandlersOfTheCore(), static fn (string $handler): bool => !preg_match(SubscriptionsPass::TRANSLATOR, $handler));
        $unknown = array_diff($this->handlersOfTheSubscriptions(), $reactions, $this->policiesOfTheCore());

        $this->assertSame([], array_values($unknown));
    }

    public function testASubscriptionIsNamedAfterTheContextOfItsReaction(): void
    {
        foreach ($this->subscriptions() as $name => $subscription) {
            foreach (self::handlersOf($subscription) as $handler) {
                $this->assertSame(self::contextOf($handler), $subscription['context'], $name);
            }

            $this->assertStringStartsWith($subscription['context'].'-', $name);
        }
    }

    public function testASubscriptionNamesItsPolicyOrItsHandler(): void
    {
        foreach ($this->subscriptions() as $name => $subscription) {
            $this->assertNotSame(isset($subscription['handler']), isset($subscription['policy']), $name);
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

    /**
     * The rule of a subscription takes an event of any of its sources of any of its types: each type is published by
     * one of its sources at least.
     */
    public function testASubscriptionListensToListedEvents(): void
    {
        $types = [new DomainEventTypes(), new IntegrationEventTypes()];

        foreach ($this->subscriptions() as $name => $subscription) {
            foreach ($subscription['events'] as $detailType) {
                $known = array_any($subscription['sources'], static fn (string $source): bool => array_any($types, static fn (EventTypes $list): bool => null !== $list->classOf($source, $detailType)));
                $this->assertTrue($known, sprintf('%s: %s is listed in no event types of its sources', $name, $detailType));
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
     * @param array{events: list<string>, handler?: string, policy?: string} $subscription
     *
     * @return list<string> its reaction: its policy, or its event handler
     */
    private static function handlersOf(array $subscription): array
    {
        return [$subscription['handler'] ?? $subscription['policy'] ?? ''];
    }

    /**
     * @param list<string> $sources
     *
     * @return list<string> the classes a detail-type is read as, from any of the sources
     */
    private static function classesOf(array $sources, string $detailType): array
    {
        $classes = [];

        foreach ($sources as $source) {
            foreach ([new DomainEventTypes(), new IntegrationEventTypes()] as $types) {
                $classes[] = $types->classOf($source, $detailType);
            }
        }

        return array_values(array_filter($classes));
    }

    /**
     * @return list<string> the classes the policy's __invoke() takes, one or a union
     */
    private static function eventsTakenBy(string $policy): array
    {
        $type = new \ReflectionMethod($policy, '__invoke')->getParameters()[0]->getType();
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        return array_values(array_map(static fn (?\ReflectionType $type): string => $type instanceof \ReflectionNamedType ? $type->getName() : '', $types));
    }

    /**
     * @return list<string>
     */
    private function policiesOfTheSubscriptions(): array
    {
        return array_values(array_filter(array_column($this->subscriptions(), 'policy')));
    }

    /**
     * @return list<string>
     */
    private function policiesOfTheCore(): array
    {
        $root = dirname(__DIR__, 2).'/vendor/prospecter/core/src/';
        $policies = [];

        foreach (glob($root.self::SERVED_CONTEXTS.'/Application/Policy/*.php', \GLOB_BRACE) ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, Policy::class, true)) {
                $policies[] = $class;
            }
        }

        return $policies;
    }

    /**
     * @return list<string>
     */
    private function handlersOfTheSubscriptions(): array
    {
        return array_merge(...array_values(array_map(self::handlersOf(...), $this->subscriptions())));
    }

    /**
     * @return array<string, array{context: string, sources: list<string>, events: list<string>, handler?: string, policy?: string, inbox: bool}>
     */
    private function subscriptions(): array
    {
        return SubscriptionsPass::listedIn(dirname(__DIR__, 2).SubscriptionsPass::FILE);
    }

    /**
     * @return list<string>
     */
    private function eventHandlersOfTheCore(): array
    {
        $root = dirname(__DIR__, 2).'/vendor/prospecter/core/src/';
        $handlers = [];

        foreach (glob($root.self::SERVED_CONTEXTS.'/Application/{*,*/*}/*Handler.php', \GLOB_BRACE) ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, EventHandler::class, true)) {
                $handlers[] = $class;
            }
        }

        return $handlers;
    }
}
