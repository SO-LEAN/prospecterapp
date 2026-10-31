<?php

namespace App;

use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

/**
 * In a Lambda of AWS (ADR 0063), BrefKernel moves the cache and the logs to /tmp, the only writable place, and copies
 * there the cache warmed in the package; elsewhere it is the kernel of Symfony.
 */
class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    /**
     * A Lambda of AWS runs its package read-only; the local ones of MiniStack and the command line, on the images of
     * Bref too, write in the mounted repository and share its cache (ADR 0054, 0082).
     */
    public function isLambda(): bool
    {
        return parent::isLambda() && !is_writable($this->getProjectDir());
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
