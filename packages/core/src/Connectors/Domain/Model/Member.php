<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Exception\NotTheAdministrator;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member as Connectors knows them from Accounts: their account, and whether they administer it.
 */
final readonly class Member
{
    public function __construct(
        public MemberId $id,
        public AccountId $accountId,
        private bool $administers,
    ) {
    }

    /**
     * @throws NotTheAdministrator
     */
    public function administeredAccount(): AccountId
    {
        return $this->administers ? $this->accountId : throw NotTheAdministrator::ofTheClients();
    }
}
