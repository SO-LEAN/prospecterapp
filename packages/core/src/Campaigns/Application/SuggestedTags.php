<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * The tags proposed to an account (ADR 0138): the predefined ones by family, in their order, then those of the
 * account that are not predefined, in the order of their names; apart, the automatic tags, to search the contacts by,
 * never put by hand: those of how a contact is reached, then those of where its organizations are (ADR 0142), in the
 * order of their names; all written with their mark.
 */
final readonly class SuggestedTags
{
    /**
     * @param array<string, list<string>> $predefined by family: `organizationType`, `relationship`, `technology`
     * @param list<string>                $own
     * @param list<string>                $automatic  those of how a contact is reached, then the geographic ones
     */
    public function __construct(
        public array $predefined,
        public array $own,
        public array $automatic,
    ) {
    }
}
