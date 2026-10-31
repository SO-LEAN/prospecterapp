<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected;

/**
 * The service Prospecting offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is
 * absent.
 */
interface WhichAreProspectedService
{
    public function __invoke(WhichAreProspected $query, WhichAreProspectedOutput $output): void;
}
