<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyUserName;

use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeMyUserName
{
    public function __construct(public UserId $userId, public string $userName)
    {
    }
}
