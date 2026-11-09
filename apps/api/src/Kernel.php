<?php

namespace App;

use App\Shared\DependencyInjection\RefusalTranslatingHandlersPass;
use App\Shared\DependencyInjection\SubscriptionsPass;
use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * In a Lambda of AWS (ADR 0063), BrefKernel moves the cache and the logs to /tmp, the only writable place, and copies
 * there the cache warmed in the package; elsewhere it is the kernel of Symfony.
 */
class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    /**
     * A Lambda of AWS runs its package read-only; the local ones of MiniStack and the command line, on the images of
     * Bref too, write in the mounted repository and share its cache (ADR 0053, 0082).
     */
    public function isLambda(): bool
    {
        return parent::isLambda() && !is_writable($this->getProjectDir());
    }

    protected function build(ContainerBuilder $container): void
    {
        // the reactions are taken off the event bus before the translators are put on it
        $container->addCompilerPass(new SubscriptionsPass(), priority: 20);
        $container->addCompilerPass(new RefusalTranslatingHandlersPass(), priority: 10);
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
