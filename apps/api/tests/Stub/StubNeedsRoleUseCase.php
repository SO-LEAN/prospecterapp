<?php

namespace Tests\App\Stub;

use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\User\Role;

final class StubNeedsRoleUseCase extends UseCase
{
    public function canBeExecutedBy(): array
    {
        return [Role::Prospector];
    }
}
