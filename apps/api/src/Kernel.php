<?php

namespace App;

use App\Shared\DependencyInjection\RefusalTranslatingHandlersPass;
use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * In a Lambda, on AWS (ADR 0063) or locally on MiniStack, BrefKernel moves the cache and the logs to /tmp, and copies
 * there the cache found in var/cache, warmed in the package on AWS; on the command line it is the kernel of Symfony.
 */
class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    /**
     * A Lambda of AWS runs its package read-only; the command line, on the image of Bref too, writes in the mounted
     * repository (ADR 0053). A local one of MiniStack serves as nobody, who cannot write it: it keeps its cache in
     * its /tmp, as on AWS.
     */
    public function isLambda(): bool
    {
        return parent::isLambda() && !is_writable($this->getProjectDir());
    }

    protected function build(ContainerBuilder $container): void
    {
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
