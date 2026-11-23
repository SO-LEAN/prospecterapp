<?php

declare(strict_types=1);

namespace App\Shared\DependencyInjection;

use App\Shared\Messaging\Subscriptions;
use App\Shared\Typed;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sorts the core's event handlers. A translator that publishes a domain event for the other contexts stays on the event
 * bus (RefusalTranslatingHandlersPass). Every other handler is a subscription's reaction. The subscriptions are listed
 * in `config/subscriptions.json`, which the infrastructure reads too. The pass takes each reaction off the event bus,
 * and the delivery (Subscriptions) reaches it through its decorators. A subscription names its reaction (`handler`), or
 * one reaction per event it listens to (`handlers`, by detail-type). It names the payloads it listens to when it does
 * not listen to all of them (`payload`). The build fails on a reaction that the list does not name. A service tagged
 * app.subscription is a subscription too. Its tag declares it, and the infrastructure does not know it: it is a
 * reaction used by the tests.
 *
 * @phpstan-type Listed array{context: string, sources: list<string>, events: list<string>, payload?: array<string, mixed>, handler?: string, handlers?: array<string, string>, inbox: bool}
 * @phpstan-type Declared array{context: string, sources: list<string>, events: list<string>, payload?: array<string, mixed>, handlers: array<string, string>, inbox: bool}
 */
final class SubscriptionsPass implements CompilerPassInterface
{
    public const string FILE = '/config/subscriptions.json';
    public const string PARAMETER = 'app.subscriptions';
    public const string DECLARED_BY_ITS_TAG = 'app.subscription';
    private const string CORE = 'Prospecter\\Core\\';
    /** A translator publishes a domain event for the other contexts: `Application\PublishAccountOpened\PublishAccountOpenedHandler`. */
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

        return new Reference(RefusalTranslatingHandlersPass::decorate($container, $id, RefusalTranslatingHandlersPass::EVENT_DECORATORS));
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
            + (isset($subscription['handler']) ? ['handler' => Typed::string($subscription['handler'])] : [])
            + (isset($subscription['handlers']) ? ['handlers' => Typed::namedStrings($subscription['handlers'])] : []);
    }

    /**
     * Gives the subscription's single reaction to every event it listens to, when it names only one.
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
