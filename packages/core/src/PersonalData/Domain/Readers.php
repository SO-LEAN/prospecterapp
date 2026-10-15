<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The readers, read from the context that knows the members of an account.
 */
interface Readers
{
    /**
     * @throws UnknownReader
     */
    public function get(MemberId $id): Reader;
}
