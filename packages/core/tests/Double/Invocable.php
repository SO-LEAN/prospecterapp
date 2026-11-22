<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * A handler of the core called as the bus of the API calls it: by its __invoke(), which the marker interfaces of the
 * handlers cannot declare, each taking its own command or event.
 */
final class Invocable
{
    public static function call(object $handler, object $message): void
    {
        new \ReflectionMethod($handler, '__invoke')->invoke($handler, $message);
    }
}
