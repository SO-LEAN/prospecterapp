<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents;

/**
 * A service Connectors offers the other contexts (ADR 0045, 0089). It refuses nothing. Its answer leaves out an unknown
 * reader, contact or organization.
 */
interface WhichMailCatchUpIntentsService
{
    public function __invoke(WhichMailCatchUpIntents $query, WhichMailCatchUpIntentsOutput $output): void;
}
