<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchEstablishments;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface SearchEstablishmentsOutput extends OutputPort
{
    /**
     * @param list<FoundEstablishment> $establishments those of the organization's municipality first
     */
    public function present(array $establishments): void;
}
