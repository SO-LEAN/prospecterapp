<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultContactTagsOutput extends OutputPort
{
    /**
     * @param list<ContactTagsResponse> $contacts in the order requested
     */
    public function present(array $contacts): void;
}
