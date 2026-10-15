<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyUserName;

use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyUserName
{
    public function __construct(public MemberId $userId, public string $userName)
    {
    }
}
