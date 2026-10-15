<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyName;

use Prospecter\Core\Domain\User\PersonName;
use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeMyName
{
    public function __construct(public UserId $userId, public PersonName $name)
    {
    }
}
