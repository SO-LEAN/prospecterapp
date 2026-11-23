<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads a member's account without restoring the account or the member (ADR 0088). The account comes with what the
 * member may change in it (`AccountPossibilities`).
 */
interface MyAccountRetriever
{
    /**
     * @throws UnknownMember|UnknownAccount
     */
    public function of(MemberId $me): MyAccount;
}
