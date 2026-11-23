<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

/**
 * When an event happens, and when a condition on its data holds, a command runs (event storming, ADR 0200, 0203). A
 * policy only turns the event into that command, filled. It depends on nothing and calls nothing: the delivery sends
 * the command through the command handlers' decorators. Its signature names the events it listens to, by a union type
 * when there are several. A condition that needs a state or personal data belongs to the command's use case.
 *
 * @method object|list<object>|null __invoke(object $event) narrowed by each policy to its events and its command
 */
interface Policy
{
}
