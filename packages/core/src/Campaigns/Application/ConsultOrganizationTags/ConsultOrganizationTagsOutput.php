<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultOrganizationTagsOutput extends OutputPort
{
    /**
     * @param list<string> $tags      put by hand, written with their mark, in the order of their names
     * @param list<string> $automatic set by Prospecter from where it is, the big city first, then its department and
     *                                its region
     */
    public function present(array $tags, array $automatic): void;
}
