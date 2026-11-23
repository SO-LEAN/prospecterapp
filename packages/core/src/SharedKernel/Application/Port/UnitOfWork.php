<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Port: what a use case writes, its state and what follows from it, is kept all at once or not at all.
 *
 * - A begin() inside an open unit joins it: only the outermost commit() writes.
 * - A discard() after its own level's commit() does nothing. A discard() without its commit() throws the whole
 *   unit away, whatever its level.
 * - Two writes of the same item make one write: the last one wins.
 * - The commit() throws a refusal detected while writing, and writes nothing.
 */
interface UnitOfWork
{
    public function begin(): void;

    /**
     * @throws BusinessException a refusal detected while writing
     */
    public function commit(): void;

    public function discard(): void;
}
