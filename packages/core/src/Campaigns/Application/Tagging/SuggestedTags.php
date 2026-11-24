<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging;

/**
 * The tags proposed to an account (ADR 0138), all written with their `#`. The predefined tags come first, by family, in
 * their order. The account's tags that are not predefined come next, sorted by name. The automatic tags are listed
 * apart, to search the contacts by and never put by hand. They are the tags for how a contact is reached, then the tags
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
