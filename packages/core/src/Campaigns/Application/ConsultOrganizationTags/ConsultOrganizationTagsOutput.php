<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultOrganizationTagsOutput extends OutputPort
{
    /**
     * @param list<string> $tags      put by hand, written with their `#`, sorted by name
     * @param list<string> $automatic set by Prospecter from where it is: the big city, then its department and its
     *                                region
     */
    public function present(array $tags, array $automatic): void;
}
