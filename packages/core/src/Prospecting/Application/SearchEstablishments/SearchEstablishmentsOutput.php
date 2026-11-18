<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchEstablishments;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface SearchEstablishmentsOutput extends OutputPort
{
    /**
     * @param list<FoundEstablishment> $establishments those of the municipality of the organization first
     */
    public function present(array $establishments): void;
}
