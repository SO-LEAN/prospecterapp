<?php

namespace App\Shared\Persistence;

/**
 * The revision of an aggregate, written in its item by its repository at each save (`revision`) and read by the
 * retrievers of its facts: a matter of persistence, the aggregate does not know it (ADR 0048). No optimistic lock
 * reads it. An item written before it has none: the revision 0, until its next save.
 */
final class Revision
{
    public const string ATTRIBUTE = 'revision';
    private const int NONE = 0;

    /**
     * @param array<string, mixed>|null $item
     */
    public static function of(?array $item): int
    {
        return (int) ($item[self::ATTRIBUTE] ?? self::NONE);
    }

    /**
     * The revision the next save of the aggregate writes.
     *
     * @param array<string, mixed>|null $stored the item as the table keeps it, null before the first save
     */
    public static function after(?array $stored): int
    {
        return self::of($stored) + 1;
    }
}
