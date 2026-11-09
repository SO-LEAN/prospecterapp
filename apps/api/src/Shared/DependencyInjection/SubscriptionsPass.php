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
 * its decorators by the delivery (Subscriptions). A reaction the list does not name fails the build.
 */
final class SubscriptionsPass implements CompilerPassInterface
{
    public const string FILE = '/config/subscriptions.json';
    public const string PARAMETER = 'app.subscriptions';
    private const string CORE = 'Prospecter\\Core\\';
    /** a translator publishes a domain event for the other contexts: `Application\PublishAccountOpened\PublishAccountOpenedHandler` */
    public const string TRANSLATOR = '/\\\\Application\\\\Publish(\w+)\\\\Publish\1Handler$/';

    public function process(ContainerBuilder $container): void
    {
        $subscriptions = $this->subscriptions($container);
        $reactions = [];

        foreach (array_keys($container->findTaggedServiceIds(RefusalTranslatingHandlersPass::EVENT_HANDLER)) as $id) {
            $handler = RefusalTranslatingHandlersPass::classOf($container, $id);

            if (!str_starts_with($handler, self::CORE) || preg_match(self::TRANSLATOR, $handler)) {
                continue;
            }

            $container->getDefinition($id)->clearTag(RefusalTranslatingHandlersPass::EVENT_HANDLER);
            $reaction = new Reference(RefusalTranslatingHandlersPass::decorate($container, $id, RefusalTranslatingHandlersPass::EVENT_DECORATORS));

            foreach ($this->namesOf($handler, $subscriptions) as $name) {
                $reactions[$name] = $reaction;
            }
        }

        $container->getDefinition(Subscriptions::class)
            ->setArgument('$reactions', ServiceLocatorTagPass::register($container, $reactions))
            ->setArgument('$subscriptions', '%'.self::PARAMETER.'%');
    }

    /**
     * Read once at the build, kept as the parameter app.subscriptions.
     *
     * @return array<string, array{context: string, sources: list<string>, events: list<string>, handler: string, inbox: bool}>
     */
    private function subscriptions(ContainerBuilder $container): array
    {
        $file = $container->getParameter('kernel.project_dir').self::FILE;
        $container->addResource(new FileResource($file));

        $subscriptions = json_decode((string) file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR);
        $container->setParameter(self::PARAMETER, $subscriptions);

        return $subscriptions;
    }

    /**
     * @param array<string, array{handler: string}> $subscriptions
     *
     * @return non-empty-list<string>
     */
    private function namesOf(string $handler, array $subscriptions): array
    {
        $names = array_keys(array_filter($subscriptions, static fn (array $subscription): bool => $subscription['handler'] === $handler));

        return [] !== $names ? $names : throw new \LogicException(sprintf('%s reacts to an event without being a subscription of %s', $handler, self::FILE));
    }
}
