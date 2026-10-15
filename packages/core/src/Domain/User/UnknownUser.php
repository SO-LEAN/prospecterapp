<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

final class UnknownUser extends \RuntimeException
{
    public function __construct(UserId $id)
    {
        parent::__construct(sprintf('User %s not found', $id));
    }
}
