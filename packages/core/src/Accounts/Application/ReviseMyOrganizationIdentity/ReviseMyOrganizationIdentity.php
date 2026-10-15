<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity;

use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ReviseMyOrganizationIdentity
{
    public function __construct(public MemberId $userId, public LegalIdentity $legalIdentity)
    {
    }
}
