<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTagSelection;

/**
 * Whom a campaign targets (ADR 0144): the contacts that carry every chosen tag and the channel's tag. The channel's tag
 * stands for the means of reaching them. All of these tags count within the 5 tags of a search.
 */
final readonly class Targeting
{
    private function __construct(
        public Channel $channel,
        public TagSelection $chosen,
    ) {
    }

    /**
     * @param list<string> $typed the chosen tags, as typed
     *
     * @throws InvalidTag
     * @throws InvalidTagSelection
     */
    public static function by(Channel $channel, array $typed): self
    {
        $targeting = new self($channel, TagSelection::of($typed));
        (void) $targeting->targeted();

        return $targeting;
    }

    /**
     * @throws InvalidTagSelection
     */
    #[\NoDiscard]
    public function targeted(): TagSelection
    {
        return TagSelection::of([...array_map(strval(...), $this->chosen->tags), (string) $this->channel->tag()]);
    }

    #[\NoDiscard]
    public function targets(ContactTags $contact): bool
    {
        return $contact->carriesAll($this->targeted());
    }

    /**
     * The tags of a search that adds contacts to the campaign: the typed tags and the channel's tag.
     *
     * @param list<string> $typed
     *
     * @throws InvalidTag
     * @throws InvalidTagSelection
     */
    #[\NoDiscard]
    public function searching(array $typed): self
    {
        return self::by($this->channel, $typed);
    }

    /**
     * Whether the contact has the means its channel needs.
     */
    #[\NoDiscard]
    public function reaches(ContactTags $contact): bool
    {
        return $contact->carriesAll(TagSelection::of([(string) $this->channel->tag()]));
    }

    /** Whether both have the same channel and the same chosen tags, in any order. */
    #[\NoDiscard]
    public function searchesAs(self $other): bool
    {
        return $this->channel === $other->channel && self::namesOf($this->chosen) === self::namesOf($other->chosen);
    }

    /**
     * @return list<string> sorted
     */
    private static function namesOf(TagSelection $selection): array
    {
        $names = array_map(static fn (Tag $tag): string => $tag->name, $selection->tags);
        sort($names);

        return $names;
    }
}
