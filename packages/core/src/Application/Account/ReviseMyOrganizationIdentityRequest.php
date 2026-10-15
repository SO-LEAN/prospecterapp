<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Domain\Organization\LegalIdentity;

final readonly class ReviseMyOrganizationIdentityRequest
{
    public function __construct(public LegalIdentity $legalIdentity)
    {
    }
}
