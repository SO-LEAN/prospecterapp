<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * The core's policies, from every context, sorted.
 */
final class PoliciesOfTheCore
{
    /**
     * @return list<class-string<Policy>>
     */
    public static function all(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $policies = [];

        foreach (glob($root.'*/Application/Policy/*.php') ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, Policy::class, true)) {
                $policies[] = $class;
            }
        }

        sort($policies);

        return $policies;
    }
}
