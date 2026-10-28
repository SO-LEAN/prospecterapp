<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the reader a member is, translated from the context that knows the members of an account (Accounts),
 * through its published query `ConsultMember` (ADR 0045).
 */
interface Readers
{
    /**
     * @throws UnknownReader
     */
    public function readerFrom(MemberId $id): Reader;
}
