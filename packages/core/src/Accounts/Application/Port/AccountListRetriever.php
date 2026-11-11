<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the identities of the accounts, without restoring them (ADR 0088).
 */
interface AccountListRetriever
{
    /**
     * The identities of the accounts newest first, after the cursor (ADR 0049).
     *
     * @return Slice<string>
     */
    public function sliceOf(?string $after, int $max): Slice;
}
