<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultSuggestedTags;

use Prospecter\Core\Campaigns\Application\SuggestedTags;

final class KeptSuggestedTags implements ConsultSuggestedTagsOutput
{
    public private(set) ?SuggestedTags $suggested = null;

    public function present(SuggestedTags $suggested): void
    {
        $this->suggested = $suggested;
    }
}
