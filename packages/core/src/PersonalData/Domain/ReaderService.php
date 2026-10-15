<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Port: the reader a member is, translated from the context that knows the members of an account (Accounts),
 * through its published query `ConsultMember` (ADR 0045).
 */
interface ReaderService
{
    /**
     * @throws UnknownReader
     */
    public function readerFrom(MemberId $id): Reader;
}
