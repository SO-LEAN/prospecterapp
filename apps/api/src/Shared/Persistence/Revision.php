<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\Typed;

/**
 * An aggregate's revision. Its repository writes it in the aggregate's item at each save (`revision`), and the
 * retrievers of its facts read it. It is a persistence matter: the aggregate does not know it (ADR 0048). The
 * aggregate's save and removal hold to the revision that its unit of work read (`RevisionLock`, ADR 0181). An item
 * written before revisions existed has none: it is at revision 0 until its next save.
 */
final class Revision
{
    public const string ATTRIBUTE = 'revision';
    public const int NONE = 0;

    /**
     * @param array<string, mixed>|null $item
     */
    public static function of(?array $item): int
    {
        return Typed::int($item[self::ATTRIBUTE] ?? self::NONE);
    }

    /**
     * The revision that the aggregate's next save writes.
     *
     * @param array<string, mixed>|null $stored the item as the table keeps it, or null before the first save
     */
    public static function after(?array $stored): int
    {
        return self::of($stored) + 1;
    }
}
