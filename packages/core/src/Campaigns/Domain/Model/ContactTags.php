<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Every tag a contact carries (ADR 0141). Its own tags are put on it by hand. It inherits tags from the organizations
 * that name it. Its automatic tags come from how it is reached. Each list is sorted by tag name.
 */
final readonly class ContactTags
{
    /**
     * @param list<Tag>          $own
     * @param list<InheritedTag> $inherited
     * @param list<Tag>          $automatic
     */
    private function __construct(public array $own, public array $inherited, public array $automatic)
    {
    }

    /**
     * A tag that the contact carries by hand and also inherits is its own, shown once. An inherited tag names every
     * organization it comes from.
     *
     * @param list<Tag>                $own
     * @param array<string, list<Tag>> $byOrganization the tags put by hand on each organization that names the
     *                                                 contact, by organization id
     */
    public static function of(array $own, array $byOrganization, Reach $reach): self
    {
        return new self(self::sorted($own), self::inheritedBesides($own, $byOrganization), self::sorted($reach->automaticTags()));
    }

    /**
     * Its own, inherited and automatic tags count alike.
     */
    #[\NoDiscard]
    public function carriesAll(TagSelection $selection): bool
    {
        return $selection->isAllAmong([...$this->own, ...array_map(static fn (InheritedTag $inherited): Tag => $inherited->tag, $this->inherited), ...$this->automatic]);
    }

    /**
     * @param list<Tag>                $own
     * @param array<string, list<Tag>> $byOrganization
     *
     * @return list<InheritedTag>
     */
    private static function inheritedBesides(array $own, array $byOrganization): array
    {
        ksort($byOrganization, SORT_STRING);
        $sources = [];

        foreach ($byOrganization as $organization => $tags) {
            foreach ($tags as $tag) {
                if (!array_any($own, $tag->equals(...))) {
                    $sources[$tag->name][] = new OrganizationId((string) $organization);
                }
            }
        }

        ksort($sources, SORT_STRING);

        return array_map(static fn (string $name, array $from): InheritedTag => new InheritedTag(Tag::written(Tag::MARK.$name), $from), array_map(strval(...), array_keys($sources)), $sources);
    }

    /**
     * @param list<Tag> $tags
     *
     * @return list<Tag>
     */
    private static function sorted(array $tags): array
    {
        usort($tags, static fn (Tag $one, Tag $other): int => strcmp($one->name, $other->name));

        return $tags;
    }
}
