<?php

namespace App\Shared\Persistence;

use Symfony\Contracts\Service\ResetInterface;

/**
 * The revision of the last read of each item a reader watches, to expose it with the resource it reads from that
 * very item (ADR 0187): the state of a request, emptied at its end; an item no reader watches is not kept, nor any
 * read of the console or of the worker.
 */
final class RevisionsRead implements ResetInterface
{
    /** @var array<string, int|null> by item watched, the revision of its last read, null before it */
    private array $revisions = [];

    public function watch(string $table, string $pk, string $sk): void
    {
        $this->revisions[Write::itemOf($table, $pk, $sk)] ??= null;
    }

    /**
     * @param array<string, mixed>|null $item null when it is absent
     */
    public function note(string $table, string $pk, string $sk, ?array $item): void
    {
        $watched = Write::itemOf($table, $pk, $sk);

        if (array_key_exists($watched, $this->revisions)) {
            $this->revisions[$watched] = Revision::of($item);
        }
    }

    public function of(string $table, string $pk, string $sk): int
    {
        return $this->revisions[Write::itemOf($table, $pk, $sk)] ?? throw new \LogicException(sprintf('The item %s / %s of %s was not read since watched', $pk, $sk, $table));
    }

    public function reset(): void
    {
        $this->revisions = [];
    }
}
