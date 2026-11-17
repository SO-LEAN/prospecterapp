<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultTags;

final readonly class ConsultTags
{
    /**
     * @param string $holder the kind of what is tagged: `organization` or `contact`
     */
    public function __construct(
        public string $memberId,
        public string $holder,
        public string $holderId,
    ) {
    }
}
