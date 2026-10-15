<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\NotFound;

final class UnknownMember extends NotFound
{
    public function __construct(MemberId $id)
    {
        parent::__construct(sprintf('Member %s not found', $id));
    }
}
