<?php

declare(strict_types=1);

namespace Tests\App\Stub;

use Prospecter\Core\UseCase\AbstractUseCase;

class StubNeedsRoleUseCaseImpl extends AbstractUseCase
{
    public function canBeExecutedBy(): array
    {
        return ['SPECIFIC_ROLE'];
    }

    public function execute(StubUseCaseRequest $request, $presenter): object
    {
        unset($request, $presenter);

        return (object) ['action' => 'executed'];
    }
}
