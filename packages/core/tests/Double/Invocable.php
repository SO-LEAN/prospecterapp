<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * A handler of the core as the bus of the API calls it: its __invoke(), which the marker interfaces of the handlers
 * cannot declare, each taking its own command or event.
 */
final class Invocable
{
    public static function of(object $handler): \Closure
    {
        return new \ReflectionMethod($handler, '__invoke')->getClosure($handler);
    }
}
