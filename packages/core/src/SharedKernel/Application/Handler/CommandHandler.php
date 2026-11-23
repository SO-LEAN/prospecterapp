<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Handler;

/**
 * Carries out one command. It changes the application's state and returns nothing. Only a query reads that state
 * back. The delivery registers every implementation on its command bus behind RefusalTranslatingCommandHandler,
 * then TransactionalCommandHandler. So a refusal detected when the unit of work commits is translated like any
 * other.
 *
 * @method void __invoke(object $command) narrowed by each handler to its command
 */
interface CommandHandler
{
}
