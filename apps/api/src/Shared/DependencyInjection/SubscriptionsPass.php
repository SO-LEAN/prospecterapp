<?php

namespace App\Shared\DependencyInjection;

use App\Shared\Messaging\Subscriptions;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Ranks the event handlers of the core: a translator publishing a domain event for the other contexts stays on the
 * event bus (RefusalTranslatingHandlersPass); every other one is the reaction of a subscription of
 * `config/subscriptions.json`, the list the infrastructure reads too, taken off the event bus and reached through
 * its decorators by the delivery (Subscriptions). A subscription names its reaction (`handler`), or one per event it
 * listens to (`handlers`, by detail-type). A reaction the list does not name fails the build. A service tagged
 * app.subscription is a subscription too, declared by its tag, unknown to the infrastructure: a reaction of the
 * tests.
 *
 * @phpstan-type Listed array{context: string, sources: list<string>, events: list<string>, handler?: string, handlers?: array<string, string>, inbox: bool}
 * @phpstan-type Declared array{context: string, sources: list<string>, events: list<string>, handlers: array<string, string>, inbox: bool}
 */
final class SubscriptionsPass implements CompilerPassInterface
{
    public const string FILE = '/config/subscriptions.json';
    public const string PARAMETER = 'app.subscriptions';
    public const string DECLARED_BY_ITS_TAG = 'app.subscription';
    private const string CORE = 'Prospecter\\Core\\';
    /** a translator publishes a domain event for the other contexts: `Application\PublishAccountOpened\PublishAccountOpenedHandler` */
    public const string TRANSLATOR = '/\\\\Application\\\\Publish(\w+)\\\\Publish\1Handler$/';

    public function process(ContainerBuilder $container): void
    {
        $subscriptions = array_map(self::withAHandlerPerEvent(...), $this->subscriptions($container));
        $reactions = $this->reactionsOfTheCore($container, $subscriptions);

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
     * @return array<string, Reference> the reaction of each subscription to each event, by Subscriptions::reactionKey()
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
     * @return array<string, array{Declared, Reference}> each subscription and its reaction, by its name
     */
    private function declaredByTheirTag(ContainerBuilder $container): array
    {
        $declared = [];

        foreach ($container->findTaggedServiceIds(self::DECLARED_BY_ITS_TAG) as $id => $tags) {
            $reaction = $this->asASubscription($container, $id);

            foreach ($tags as $tag) {
                $declared[$tag['subscription']] = [['context' => $tag['context'], 'sources' => [$tag['source']], 'events' => [$tag['event']], 'handlers' => [$tag['event'] => RefusalTranslatingHandlersPass::classOf($container, $id)], 'inbox' => $tag['inbox']], $reaction];
            }
        }

        return $declared;
    }

    /**
     * Taken off the event bus, reached through its decorators by the delivery.
     */
    private function asASubscription(ContainerBuilder $container, string $id): Reference
    {
        $container->getDefinition($id)->clearTag(RefusalTranslatingHandlersPass::EVENT_HANDLER);

        return new Reference(RefusalTranslatingHandlersPass::decorate($container, $id, RefusalTranslatingHandlersPass::EVENT_DECORATORS));
    }

    /**
     * Read once at the build.
     *
     * @return array<string, Listed>
     */
    private function subscriptions(ContainerBuilder $container): array
    {
        $file = $container->getParameter('kernel.project_dir').self::FILE;
        $container->addResource(new FileResource($file));

        return json_decode((string) file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * Its reaction to every event it listens to, when it names only one.
     *
     * @param Listed $subscription
     *
     * @return Declared
     */
    private static function withAHandlerPerEvent(array $subscription): array
    {
        $handlers = $subscription['handlers'] ?? array_fill_keys($subscription['events'], $subscription['handler'] ?? null);
        unset($subscription['handler']);

        return ['handlers' => array_map(static fn (?string $handler): string => $handler ?? throw new \LogicException(sprintf('A subscription of %s names its handler or its handlers', self::FILE)), $handlers)] + $subscription;
    }

    /**
     * @param array<string, Declared> $subscriptions
     *
     * @return non-empty-list<string> the keys of the reactions the handler is, by Subscriptions::reactionKey()
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
