<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final class UnknownMember extends NotFound
{
    public static function identifiedBy(MemberId $id): self
    {
        return new self(sprintf('Member %s not found', $id));
    }
}
