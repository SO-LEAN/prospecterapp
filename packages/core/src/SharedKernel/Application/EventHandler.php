<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * Reacts to one event from another context, as the member who acted (the event's executedBy). It reuses the
 * intention methods, so that member's rights still apply. The delivery registers every implementation on its
 * event bus behind RefusalTranslatingEventHandler, then TransactionalEventHandler. So the reaction joins the unit
 * of work of the command that published the event, and can refuse or fail that command.
 *
 * @method void __invoke(object $event) narrowed by each handler to its event
 */
interface EventHandler
{
}
