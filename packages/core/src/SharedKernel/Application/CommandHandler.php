<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * Carries out one command: changes the state of the application, returns nothing. A query is the only
 * way to read it back. The delivery registers every implementation on its command bus.
 */
interface CommandHandler
{
}
