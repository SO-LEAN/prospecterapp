<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;

/**
 * The core's event handlers, from every context, sorted. The shared kernel holds no use case: its Handler folder holds
 * what the handlers implement and their decorators.
 */
final class EventHandlersOfTheCore
{
    private const string SHARED_KERNEL = 'SharedKernel/';

    /**
     * @return list<class-string<EventHandler>>
     */
    public static function all(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $handlers = [];

        foreach (glob($root.'*/Application/*/*Handler.php') ?: [] as $file) {
            if (str_starts_with(substr($file, strlen($root)), self::SHARED_KERNEL)) {
                continue;
            }

            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (is_a($class, EventHandler::class, true)) {
                $handlers[] = $class;
            }
        }

        sort($handlers);

        return $handlers;
    }
}
