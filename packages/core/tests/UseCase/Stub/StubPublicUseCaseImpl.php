<?php

declare(strict_types=1);

namespace Tests\Core\UseCase\Stub;

use Prospecter\Core\UseCase\AbstractUseCase;

final class StubPublicUseCaseImpl extends AbstractUseCase
{
    /**
     * @param list<string> $roles
     */
    public function __construct(private readonly array $roles = [])
    {
    }

    public function canBeExecutedBy(): array
    {
        return $this->roles;
    }

    public function execute(StubUseCaseRequest $request, object $presenter): \stdClass
    {
        return (object) ['action' => 'executed'];
    }
}
