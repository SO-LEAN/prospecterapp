<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\NotFound;

final class UnknownReader extends NotFound
{
    public function __construct(MemberId $id)
    {
        parent::__construct(sprintf('Reader %s not found', $id));
    }
}
