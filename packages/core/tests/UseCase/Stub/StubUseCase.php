<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\Stub;

use Prospecter\Core\Application\UseCase;

final class StubUseCase extends UseCase
{
    public function execute(StubUseCaseRequest $request, object $presenter): \stdClass
    {
        return (object) ['action' => 'executed'];
    }
}
