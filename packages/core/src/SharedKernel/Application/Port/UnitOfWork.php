<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * Port: what a use case writes, its state and what follows from it, is kept all at once or not at all.
 *
 * - A begin() within an open unit joins it: only the outermost commit() writes.
 * - A discard() after the commit() of its own level does nothing; a discard() without its commit() throws the
 *   whole unit away, whatever its level.
 * - Two writes of the same item make one, the last one wins.
 * - A refusal detected when writing is thrown by the commit(), nothing written.
 */
interface UnitOfWork
{
    public function begin(): void;

    /**
     * @throws BusinessException a refusal detected when writing
     */
    public function commit(): void;

    public function discard(): void;
}
