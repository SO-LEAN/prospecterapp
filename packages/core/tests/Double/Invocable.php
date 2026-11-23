<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * A core handler, called the way the API's bus calls it: through its __invoke(). The handlers' marker interfaces
 * cannot declare that method, because each handler takes its own command or event.
 */
final class Invocable
{
    public static function call(object $handler, object $message): void
    {
        new \ReflectionMethod($handler, '__invoke')->invoke($handler, $message);
    }
}
