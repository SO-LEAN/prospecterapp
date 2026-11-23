<?php

declare(strict_types=1);

namespace Tests\App\Base;

use App\Demo\DemoCredentials;
use App\Demo\DemoMembers;
use App\SignIn\UserPool;

/**
 * The user pool every API test starts from (ADR 0052): recreated once in each process of the tests, with the members of
 * the demonstration. Each later test gets them back as they were, and the pool is kept (DemoMembers).
 */
final class MembersOfTheDemonstration
{
    private static bool $made = false;

    public function __construct(
        private readonly UserPool $pool,
        private readonly DemoCredentials $demoCredentials,
        private readonly DemoMembers $demoMembers,
    ) {
    }

    public function restore(): void
    {
        if (self::$made) {
            $this->demoMembers->restore();

            return;
        }

        $this->pool->recreate();
        $this->demoCredentials->register();
        self::$made = true;
    }
}
