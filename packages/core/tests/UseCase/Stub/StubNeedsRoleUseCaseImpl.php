<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\Stub;

use Prospecter\Core\UseCase\AbstractUseCase;

final class StubNeedsRoleUseCaseImpl extends AbstractUseCase
{
    public function canBeExecutedBy(): array
    {
        return ['SPECIFIC_ROLE'];
    }

    public function execute(StubUseCaseRequest $request, object $presenter): \stdClass
    {
        return (object) ['action' => 'executed'];
    }
}
