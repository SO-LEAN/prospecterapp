<?php

declare(strict_types=1);

namespace App\Shared\DependencyInjection;

use App\Shared\CommandBus;
use App\Shared\Messaging\HandlerReaction;
use App\Shared\Messaging\PolicyReaction;
use App\Shared\Messaging\Subscriptions;
use App\Shared\Typed;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Gives each subscription its reaction. The subscriptions are listed in `config/subscriptions.json`, which the
 * infrastructure reads too. A subscription names its policy (`policy`, ADR 0203). The delivery (Subscriptions) gives
 * the event to the policy and sends its commands to the command bus (PolicyReaction). A subscription may still name
 * an event handler of the core that is not a policy yet (`handler`). A translator that publishes a domain event for the other contexts stays on the event bus
 * (RefusalTranslatingHandlersPass). The pass takes every other event handler off the event bus, and the delivery
 * reaches it through its decorators (HandlerReaction). The build fails on such a handler that the list does not name.
 * A subscription names the payloads it listens to when it does not listen to all of them (`payload`). A service tagged
 * app.subscription is a subscription too. Its tag declares it, and the infrastructure does not know it: it is a
 * reaction used by the tests.
 *
 * @phpstan-type Listed array{context: string, sources: list<string>, events: list<string>, payload?: array<string, mixed>, policy?: string, handler?: string, inbox: bool}
 * @phpstan-type Declared array{context: string, sources: list<string>, events: list<string>, payload?: array<string, mixed>, handlers: array<string, string>, inbox: bool}
 */
final class SubscriptionsPass implements CompilerPassInterface
{
    public const string FILE = '/config/subscriptions.json';
    public const string PARAMETER = 'app.subscriptions';
    public const string DECLARED_BY_ITS_TAG = 'app.subscription';
    private const string CORE = 'Prospecter\\Core\\';
    /**
     * A translator publishes a domain event for the other contexts, in its context's application or in one of its modules:
     * `Application\PublishAccountOpened\PublishAccountOpenedHandler`, `Application\Appointment\PublishSlotBooked\PublishSlotBookedHandler`.
     */
    public const string TRANSLATOR = '/\\\\Application\\\\(?:\w+\\\\)?Publish(\w+)\\\\Publish\1Handler$/';

    public function process(ContainerBuilder $container): void
    {
        $subscriptions = array_map(self::withAHandlerPerEvent(...), $this->subscriptions($container));
        $reactions = $this->policyReactions($container, $subscriptions) + $this->reactionsOfTheCore($container, $subscriptions);

        foreach ($this->declaredByTheirTag($container) as $name => [$declared, $reaction]) {
            $subscriptions[$name] = $declared;
            $reactions[Subscriptions::reactionKey($name, $declared['events'][0])] = $reaction;
        }

        $container->setParameter(self::PARAMETER, $subscriptions);
        $container->getDefinition(Subscriptions::class)
            ->setArgument('$reactions', ServiceLocatorTagPass::register($container, $reactions))
            ->setArgument('$subscriptions', '%'.self::PARAMETER.'%');
    }

    /**
     * @param array<string, Declared> $subscriptions
     *
     * @return array<string, Reference> each subscription's reaction to each event, by Subscriptions::reactionKey()
     */
    private function reactionsOfTheCore(ContainerBuilder $container, array $subscriptions): array
    {
        $reactions = [];

        foreach (array_keys($container->findTaggedServiceIds(RefusalTranslatingHandlersPass::EVENT_HANDLER)) as $id) {
            $handler = RefusalTranslatingHandlersPass::classOf($container, $id);

            if (!str_starts_with($handler, self::CORE) || preg_match(self::TRANSLATOR, $handler)) {
                continue;
            }

            $reaction = $this->asASubscription($container, $id);

            foreach ($this->keysOf($handler, $subscriptions) as $key) {
                $reactions[$key] = $reaction;
            }
        }

        return $reactions;
    }

    /**
     * @param array<string, Declared> $subscriptions
     *
     * @return array<string, Reference> each subscription's policy reaction to each event, by Subscriptions::reactionKey()
     */
    private function policyReactions(ContainerBuilder $container, array $subscriptions): array
    {
        $reactions = [];

        foreach ($subscriptions as $name => $subscription) {
            foreach ($subscription['handlers'] as $event => $policy) {
                if (is_a($policy, Policy::class, true)) {
                    $reactions[Subscriptions::reactionKey($name, $event)] = $this->reactionOfThePolicy($container, $policy);
                }
            }
        }

        return $reactions;
    }

    private function reactionOfThePolicy(ContainerBuilder $container, string $policy): Reference
    {
        $id = $policy.'.reaction';

        if (!$container->hasDefinition($id)) {
            $container->register($id, PolicyReaction::class)->setArguments(['$policy' => new Definition($policy), '$commands' => new Reference(CommandBus::class)]);
        }

        return new Reference($id);
    }

    /**
     * @return array<string, array{Declared, Reference}> each subscription and its reaction, by the subscription's name
     */
    private function declaredByTheirTag(ContainerBuilder $container): array
    {
        $declared = [];

        foreach ($container->findTaggedServiceIds(self::DECLARED_BY_ITS_TAG) as $id => $tags) {
            $reaction = $this->asASubscription($container, $id);

            foreach (Typed::documents($tags) as $tag) {
                $event = Typed::string($tag['event']);
                $declared[Typed::string($tag['subscription'])] = [['context' => Typed::string($tag['context']), 'sources' => [Typed::string($tag['source'])], 'events' => [$event], 'handlers' => [$event => RefusalTranslatingHandlersPass::classOf($container, $id)], 'inbox' => Typed::bool($tag['inbox'])], $reaction];
            }
        }

        return $declared;
    }

    /**
     * Takes the handler off the event bus. The delivery reaches it through its decorators.
     */
    private function asASubscription(ContainerBuilder $container, string $id): Reference
    {
        $container->getDefinition($id)->clearTag(RefusalTranslatingHandlersPass::EVENT_HANDLER);
        $container->register($id.'.reaction', HandlerReaction::class)->setArgument('$handler', new Reference(RefusalTranslatingHandlersPass::decorate($container, $id, RefusalTranslatingHandlersPass::EVENT_DECORATORS)));

        return new Reference($id.'.reaction');
    }

    /**
     * Reads the list once, at build time.
     *
     * @return array<string, Listed>
     */
    private function subscriptions(ContainerBuilder $container): array
    {
        $file = Typed::string($container->getParameter('kernel.project_dir')).self::FILE;
        $container->addResource(new FileResource($file));

        return self::listedIn($file);
    }

    /**
     * Reads the subscriptions a file lists, and checks each one as it reads it.
     *
     * @return array<string, Listed>
     */
    public static function listedIn(string $file): array
    {
        return array_map(self::listed(...), Typed::document(json_decode((string) file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR)));
    }

    /**
     * @return Listed
     */
    private static function listed(mixed $subscription): array
    {
        $subscription = Typed::document($subscription);

        return ['context' => Typed::string($subscription['context']), 'sources' => Typed::strings($subscription['sources']), 'events' => Typed::strings($subscription['events']), 'inbox' => Typed::bool($subscription['inbox'])]
            + (isset($subscription['payload']) ? ['payload' => Typed::document($subscription['payload'])] : [])
            + (isset($subscription['policy']) ? ['policy' => Typed::string($subscription['policy'])] : [])
            + (isset($subscription['handler']) ? ['handler' => Typed::string($subscription['handler'])] : []);
    }

    /**
     * Gives the subscription's reaction, its policy or its handler, to every event it listens to.
     *
     * @param Listed $subscription
     *
     * @return Declared
     */
    private static function withAHandlerPerEvent(array $subscription): array
    {
        $handlers = array_fill_keys($subscription['events'], $subscription['policy'] ?? $subscription['handler'] ?? null);
        unset($subscription['policy'], $subscription['handler']);

        return ['handlers' => array_map(static fn (?string $handler): string => $handler ?? throw new \LogicException(sprintf('A subscription of %s names its policy or its handler', self::FILE)), $handlers)] + $subscription;
    }

    /**
     * @param array<string, Declared> $subscriptions
     *
     * @return non-empty-list<string> the keys of the reactions that the handler is, by Subscriptions::reactionKey()
     */
    private function keysOf(string $handler, array $subscriptions): array
    {
        $keys = [];

        foreach ($subscriptions as $name => $subscription) {
            foreach (array_keys($subscription['handlers'], $handler, true) as $event) {
                $keys[] = Subscriptions::reactionKey($name, $event);
            }
        }

        return [] !== $keys ? $keys : throw new \LogicException(sprintf('%s reacts to an event without being a subscription of %s', $handler, self::FILE));
    }
}
