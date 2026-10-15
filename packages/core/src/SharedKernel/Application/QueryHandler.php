<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

/**
 * Answers one query: reads the state of the application, changes nothing. The delivery calls it directly,
 * through a RefusalTranslatingHandler.
 *
 * @method mixed __invoke(object $query) narrowed by each handler to its query and its response
 */
interface QueryHandler
{
}
