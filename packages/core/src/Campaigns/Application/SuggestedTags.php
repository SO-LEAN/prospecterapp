<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * The tags proposed to an account (ADR 0138): the predefined ones by family, in their order, then those of the
 * account that are not predefined, in the order of their names; all written with their mark.
 */
final readonly class SuggestedTags
{
    /**
     * @param array<string, list<string>> $predefined by family: `organizationType`, `relationship`, `technology`
     * @param list<string>                $own
     */
    public function __construct(
        public array $predefined,
        public array $own,
    ) {
    }
}
