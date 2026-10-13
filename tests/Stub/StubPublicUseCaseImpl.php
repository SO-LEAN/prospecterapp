<?php

declare(strict_types=1);

namespace Tests\App\Stub;

use Solean\CleanProspecter\UseCase\AbstractUseCase;

class StubPublicUseCaseImpl extends AbstractUseCase
{
    /**
     * StubPublicUseCaseImpl constructor.
     */
    public function __construct(private $roles = [])
    {
    }

    /**
     * empty mean everybody.
     */
    public function canBeExecutedBy(): array
    {
        return $this->roles;
    }

    public function execute(StubUseCaseRequest $request, $presenter): object
    {
        unset($request, $presenter);

        return (object) ['action' => 'executed'];
    }
}
