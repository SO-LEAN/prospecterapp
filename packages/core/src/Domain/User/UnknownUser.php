<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

use Prospecter\Core\Domain\Shared\NotFound;

final class UnknownUser extends NotFound
{
    public function __construct(UserId $id)
    {
        parent::__construct(sprintf('User %s not found', $id));
    }
}
