<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * Answers one query: reads the state of the application, changes nothing, and presents what it read to the output
 * port its client gives (ADR 0085). The delivery calls it directly, through a RefusalTranslatingHandler.
 *
 * @method void __invoke(object $query, object $output) narrowed by each handler to its query and its output port
 */
interface QueryHandler
{
}
