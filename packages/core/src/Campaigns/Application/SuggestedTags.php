<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * The tags proposed to an account (ADR 0138), all written with their `#`. First come the predefined tags by family, in
 * their order. Then come the account's tags that are not predefined, sorted by name. Separately come the automatic
 * tags, to search the contacts by and never put by hand. These are the tags for how a contact is reached, then the tags
 * for where its organizations are, sorted by name (ADR 0142).
 */
final readonly class SuggestedTags
{
    /**
     * @param array<string, list<string>> $predefined by family: `organizationType`, `relationship`, `technology`
     * @param list<string>                $own
     * @param list<string>                $automatic  the tags for how a contact is reached, then the geographic tags
     */
    public function __construct(
        public array $predefined,
        public array $own,
        public array $automatic,
    ) {
    }
}
