<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ReviseMyOrganizationIdentity;

use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\User\UserId;

final readonly class ReviseMyOrganizationIdentity
{
    public function __construct(public UserId $userId, public LegalIdentity $legalIdentity)
    {
    }
}
