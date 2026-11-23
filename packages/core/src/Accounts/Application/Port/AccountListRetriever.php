<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Search\Slice;

/**
 * Reads the accounts' ids without restoring the accounts (ADR 0088).
 */
interface AccountListRetriever
{
    /**
     * Returns the accounts' ids, newest first, after the cursor (ADR 0049).
     *
     * @return Slice<string>
     */
    public function sliceOf(?string $after, int $max): Slice;
}
