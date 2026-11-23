<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Port;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the reader a member is. It translates from the context that knows an account's members (Accounts), through its
 * published service `ConsultMembersService` (ADR 0045, 0089).
 */
interface Readers
{
    /**
     * @throws UnknownReader
     */
    public function readerFrom(MemberId $id): Reader;
}
