<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTagSelection;

/**
 * The tags a member chooses to find the contacts carrying every one of them: 1 to 5, each written as a tag, a tag
 * typed twice chosen once.
 */
final readonly class TagSelection
{
    public const int MAX = 5;

    /**
     * @param list<Tag> $tags
     */
    private function __construct(public array $tags)
    {
    }

    /**
     * @param list<string> $typed
     *
     * @throws InvalidTag
     * @throws InvalidTagSelection
     */
    public static function of(array $typed): self
    {
        $tags = [];

        foreach ($typed as $text) {
            $tag = Tag::written($text);
            $tags[$tag->name] = $tag;
        }

        $size = count($tags);

        return $size >= 1 && $size <= self::MAX ? new self(array_values($tags)) : throw InvalidTagSelection::ofSize($size, self::MAX);
    }

    /**
     * @param list<Tag> $carried
     */
    #[\NoDiscard]
    public function isAllAmong(array $carried): bool
    {
        return array_all($this->tags, static fn (Tag $chosen): bool => array_any($carried, $chosen->equals(...)));
    }
}
