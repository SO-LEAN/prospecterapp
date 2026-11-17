<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultOrganizationTagsOutput extends OutputPort
{
    /**
     * @param list<string> $tags written with their mark, in the order of their names
     */
    public function present(array $tags): void;
}
