<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * The English words of a post-it, read from the names of the code, in sentence case as people write on post-its.
 */
final readonly class Wording
{
    /** A run of capitals before a word is an acronym (CSVFile), then each capital starts a word. */
    private const string WORDS = '/[A-Z]+(?=[A-Z][a-z])|[A-Z]?[a-z0-9]+|[A-Z]+/';

    private const string REACTION_SUFFIX = '/(Policy|Handler)$/';

    /** A query asks to view something: the read model is that thing. */
    private const string QUERY_PREFIX = '/^Consult(?=[A-Z])/';

    private const string ROLE_PREFIX = '/^ROLE_/';

    /** Who stands behind an entrance of the delivery (ContextStorming::actorsEntering()). */
    private const array ACTORS = [
        'ROLE_USER' => 'Member',
        'public' => 'Anyone with the link',
        'mcp' => 'Member\'s AI',
    ];

    public static function ofIntention(string $name): string
    {
        preg_match_all(self::WORDS, $name, $matches);

        return ucfirst(implode(' ', array_map(self::wordOf(...), $matches[0])));
    }

    public static function ofReaction(string $name): string
    {
        return self::ofIntention(self::without(self::REACTION_SUFFIX, $name));
    }

    public static function ofReadModel(string $query, ?string $presented): string
    {
        return self::ofIntention($presented ?? self::without(self::QUERY_PREFIX, $query));
    }

    public static function ofActor(string $role): string
    {
        return self::ACTORS[$role] ?? ucfirst(str_replace('_', ' ', strtolower(self::without(self::ROLE_PREFIX, $role))));
    }

    private static function wordOf(string $word): string
    {
        return self::isAnAcronym($word) ? $word : strtolower($word);
    }

    private static function isAnAcronym(string $word): bool
    {
        return \strlen($word) > 1 && strtoupper($word) === $word && !ctype_digit($word);
    }

    private static function without(string $pattern, string $name): string
    {
        return (string) preg_replace($pattern, '', $name);
    }
}
