<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultSuggestedTags;

use Prospecter\Core\Campaigns\Application\SuggestedTags;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultSuggestedTagsOutput extends OutputPort
{
    public function present(SuggestedTags $suggested): void;
}
