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
        return array_values(array_filter(self::classesOfTheFolders(), static fn (string $class): bool => is_a($class, Policy::class, true)));
    }

    /**
     * Every class of a context's `Application/Policy`, a policy or not, sorted.
     *
     * @return list<class-string>
     */
    public static function classesOfTheFolders(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $classes = [];

        foreach (glob($root.'*/Application/Policy/*.php') ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }
}
