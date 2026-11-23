<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultContactTags;

/** The tags a contact carries, written with their `#` (ADR 0141). Each list is sorted by name. */
final readonly class ContactTagsResponse
{
    /**
     * @param list<string>               $own       put on it by hand; only these are taken off
     * @param list<InheritedTagResponse> $inherited from the organizations that name it, put on them by hand or set by
     *                                              where they are; its own tags are left out
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
     * @return list<string> every tag it carries, once each, sorted by name
     */
    public function carried(): array
    {
        $names = [...$this->own, ...array_map(static fn (InheritedTagResponse $tag): string => $tag->name, $this->inherited), ...$this->automatic];
        sort($names, SORT_STRING);

        return $names;
    }
}
