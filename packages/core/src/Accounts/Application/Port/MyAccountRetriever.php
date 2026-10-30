<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\Port;

use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownAccount;
use Prospecter\Core\Accounts\Domain\Exception\UnknownMember;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Reads the account of a member without restoring it nor them (ADR 0088), with what they may change of it
 * (`AccountPossibilities`).
 */
interface MyAccountRetriever
{
    /**
     * @throws UnknownMember|UnknownAccount
     */
    public function of(MemberId $me): MyAccount;
}
