<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * The core's event handlers, from every context, sorted.
 */
final class EventHandlersOfTheCore
{
    /**
     * @return list<class-string<EventHandler>>
     */
    public static function all(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $handlers = [];

        foreach (glob($root.'*/Application/*/*Handler.php') ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, EventHandler::class, true)) {
                $handlers[] = $class;
            }
        }

        sort($handlers);

        return $handlers;
    }
}
