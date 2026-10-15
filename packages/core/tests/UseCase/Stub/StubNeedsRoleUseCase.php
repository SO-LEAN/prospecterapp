<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\Stub;

use Prospecter\Core\Application\UseCase;
use Prospecter\Core\Domain\User\Role;

final class StubNeedsRoleUseCase extends UseCase
{
    public function canBeExecutedBy(): array
    {
        return [Role::Prospector];
    }

    public function execute(StubUseCaseRequest $request, object $presenter): \stdClass
    {
        return (object) ['action' => 'executed'];
    }
}
