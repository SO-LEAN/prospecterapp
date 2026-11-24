<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\ConsultSuggestedTags;

final readonly class ConsultSuggestedTags
{
    public function __construct(
        public string $memberId,
    ) {
    }
}
