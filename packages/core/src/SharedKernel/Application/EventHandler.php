<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * Reacts to one event of another context, as the member who did the gesture (its executedBy): it reuses the
 * intention methods, so the rights of that member still apply. The delivery registers every implementation on
 * its event bus.
 *
 * @method void __invoke(object $event) narrowed by each handler to its event
 */
interface EventHandler
{
}
