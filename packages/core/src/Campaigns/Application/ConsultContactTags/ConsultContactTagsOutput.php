<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultContactTagsOutput extends OutputPort
{
    /**
     * @param list<ContactTagsResponse> $contacts in the order requested
     */
    public function present(array $contacts): void;
}
