<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps the revision each watched item had when last read, so that a resource can expose the revision of the item it
 * was read from (ADR 0187). It holds one request's state and is emptied at the end; unwatched items, and the reads of
 * the console and the worker, are not kept.
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
