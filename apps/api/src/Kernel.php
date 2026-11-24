<?php

declare(strict_types=1);

namespace App;

use App\Shared\DependencyInjection\RefusalTranslatingHandlersPass;
use App\Shared\DependencyInjection\RefusingToolsPass;
use App\Shared\DependencyInjection\SubscriptionsPass;
use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * In an AWS Lambda (ADR 0063), BrefKernel moves the cache and the logs to /tmp, the only writable place. It copies
 * there the cache that was warmed in the package. Elsewhere, it is Symfony's kernel.
 */
class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    /**
     * An AWS Lambda runs its package read-only. MiniStack's local Lambdas and the command line also run on Bref's
     * images. They write in the mounted repository and share its cache (ADR 0053, 0082).
     */
    public function isLambda(): bool
    {
        return parent::isLambda() && !is_writable($this->getProjectDir());
    }

    protected function build(ContainerBuilder $container): void
    {
        // The subscriptions check that every event handler of the core translates, before the translators join the
        // event bus.
        $container->addCompilerPass(new SubscriptionsPass(), priority: 20);
        $container->addCompilerPass(new RefusalTranslatingHandlersPass(), priority: 10);
        $container->addCompilerPass(new RefusingToolsPass(), priority: -10);
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
