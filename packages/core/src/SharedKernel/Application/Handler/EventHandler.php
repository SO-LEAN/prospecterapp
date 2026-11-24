<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

/**
 * Translates a domain event into what the other contexts may know: an integration event or an aggregate's facts
 * (`Publish…Handler`). The delivery registers every implementation on its event bus. So the translator runs within
 * the command that published the event, inside that command's decorators, and its publication joins the command's
 * unit of work. A reaction to an event is a Policy, never an event handler (ADR 0203).
 *
 * @method void __invoke(object $event) narrowed by each handler to its event
 */
interface EventHandler
{
}
