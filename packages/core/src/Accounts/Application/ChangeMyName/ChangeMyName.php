<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyName;

use Prospecter\Core\Accounts\Domain\PersonName;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyName
{
    public function __construct(public MemberId $userId, public PersonName $name)
    {
    }
}
