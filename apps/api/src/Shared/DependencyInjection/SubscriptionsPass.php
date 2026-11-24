<?php

declare(strict_types=1);

namespace App\Shared\DependencyInjection;

use App\Shared\Messaging\PolicyReaction;
use App\Shared\Messaging\Subscriptions;
use App\Shared\Typed;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Gives each subscription its reaction. The subscriptions are listed in `config/subscriptions.json`, which the
 * infrastructure reads too. A subscription names its policy (`policy`, ADR 0203). The delivery (Subscriptions) gives
 * the event to the policy and sends its commands to the command bus (PolicyReaction). A subscription names the
 * payloads it listens to when it does not listen to all of them (`payload`). An event handler of the core is a
 * translator that publishes a domain event for the other contexts, on the event bus (RefusalTranslatingHandlersPass):
 * the build fails on any other. A service tagged app.subscription is a subscription too. Its tag declares it, and the
 * infrastructure does not know it: it is a policy used by the tests.
 *
 * @phpstan-type Declared array{context: string, sources: list<string>, events: list<string>, payload?: array<string, mixed>, policy: string, inbox: bool}
 */
final class SubscriptionsPass implements CompilerPassInterface
{
    public const string FILE = '/config/subscriptions.json';
    public const string PARAMETER = 'app.subscriptions';
    public const string DECLARED_BY_ITS_TAG = 'app.subscription';
    private const string CORE = 'Prospecter\\Core\\';
    private const string COMMAND_BUS = 'command.bus';
    /**
     * A translator publishes a domain event for the other contexts, in its context's application or in one of its modules:
     * `Application\PublishAccountOpened\PublishAccountOpenedHandler`, `Application\Appointment\PublishSlotBooked\PublishSlotBookedHandler`.
     */
    public const string TRANSLATOR = '/\\\\Application\\\\(?:\w+\\\\)?Publish(\w+)\\\\Publish\1Handler$/';

    public function process(ContainerBuilder $container): void
    {
        $this->ensureEveryEventHandlerIsATranslator($container);
        $subscriptions = $this->subscriptions($container);
        $reactions = [];

        foreach ($subscriptions as $name => $subscription) {
            $reactions += $this->reactionsOf($name, $subscription, $this->reactionOfThePolicy($container, $subscription['policy']));
        }

        foreach ($this->declaredByTheirTag($container) as $name => [$declared, $reaction]) {
            $subscriptions[$name] = $declared;
            $reactions += $this->reactionsOf($name, $declared, $reaction);
        }

        $container->setParameter(self::PARAMETER, $subscriptions);
        $container->getDefinition(Subscriptions::class)
            ->setArgument('$reactions', ServiceLocatorTagPass::register($container, $reactions))
            ->setArgument('$subscriptions', '%'.self::PARAMETER.'%');
    }

    /**
     * Every reaction of the core is a policy, so an event handler of the core only translates (ADR 0203).
     */
    private function ensureEveryEventHandlerIsATranslator(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(RefusalTranslatingHandlersPass::EVENT_HANDLER)) as $id) {
            $handler = RefusalTranslatingHandlersPass::classOf($container, $id);

            if (str_starts_with($handler, self::CORE) && !preg_match(self::TRANSLATOR, $handler)) {
                throw new \LogicException(sprintf('%s reacts to an event without being a policy of %s', $handler, self::FILE));
            }
        }
    }

    /**
     * @param Declared $subscription
     *
     * @return array<string, Reference> the subscription's reaction to each event it listens to, by Subscriptions::reactionKey()
     */
    private function reactionsOf(string $name, array $subscription, Reference $reaction): array
    {
        $reactions = [];

        foreach ($subscription['events'] as $event) {
            $reactions[Subscriptions::reactionKey($name, $event)] = $reaction;
        }

        return $reactions;
    }

    private function reactionOfThePolicy(ContainerBuilder $container, string $policy): Reference
    {
        $id = $policy.'.reaction';

        if (!$container->hasDefinition($id)) {
            $container->register($id, PolicyReaction::class)->setArguments(['$policy' => new Definition($policy), '$commandBus' => new Reference(self::COMMAND_BUS)]);
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
            $container->register($id.'.reaction', PolicyReaction::class)->setArguments(['$policy' => new Reference($id), '$commandBus' => new Reference(self::COMMAND_BUS)]);

            foreach (Typed::documents($tags) as $tag) {
                $declared[Typed::string($tag['subscription'])] = [['context' => Typed::string($tag['context']), 'sources' => [Typed::string($tag['source'])], 'events' => [Typed::string($tag['event'])], 'policy' => RefusalTranslatingHandlersPass::classOf($container, $id), 'inbox' => Typed::bool($tag['inbox'])], new Reference($id.'.reaction')];
            }
        }

        return $declared;
    }

    /**
     * Reads the list once, at build time.
     *
     * @return array<string, Declared>
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
     * @return array<string, Declared>
     */
    public static function listedIn(string $file): array
    {
        return array_map(self::listed(...), Typed::document(json_decode((string) file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR)));
    }

    /**
     * @return Declared
     */
    private static function listed(mixed $subscription): array
    {
        $subscription = Typed::document($subscription);

        return ['context' => Typed::string($subscription['context']), 'sources' => Typed::strings($subscription['sources']), 'events' => Typed::strings($subscription['events']), 'policy' => Typed::string($subscription['policy']), 'inbox' => Typed::bool($subscription['inbox'])]
            + (isset($subscription['payload']) ? ['payload' => Typed::document($subscription['payload'])] : []);
    }
}
