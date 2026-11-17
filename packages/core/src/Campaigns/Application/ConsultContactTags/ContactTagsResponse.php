<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

/**
 * The tags a contact carries, each list in the order of their names, written with their mark (ADR 0141).
 */
final readonly class ContactTagsResponse
{
    /**
     * @param list<string>               $own       put on it by hand, the only ones taken off
     * @param list<InheritedTagResponse> $inherited from the organizations naming it, its own excluded
     * @param list<string>               $automatic set by Prospecter from how it is reached
     */
    public function __construct(
        public string $contactId,
        public array $own,
        public array $inherited,
        public array $automatic,
    ) {
    }

    /**
     * @return list<string> every tag it carries, once each, in the order of their names
     */
    public function carried(): array
    {
        $names = [...$this->own, ...array_map(static fn (InheritedTagResponse $tag): string => $tag->name, $this->inherited), ...$this->automatic];
        sort($names, SORT_STRING);

        return $names;
    }
}
