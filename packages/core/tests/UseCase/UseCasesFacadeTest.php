<?php

declare(strict_types=1);

namespace Tests\Core\UseCase;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Application\UseCasesFacade;
use Tests\Core\UseCase\Stub\StubUseCase;
use Tests\Core\UseCase\Stub\StubUseCaseRequest;

/**
 * The facade is how the API calls the use cases today, until a command bus replaces it (ADR 0020).
 */
class UseCasesFacadeTest extends TestCase
{
    private UseCasesFacade $facade;

    protected function setUp(): void
    {
        $this->facade = new UseCasesFacade();
        $this->facade->addUseCase(new StubUseCase());
    }

    public function testAUseCaseIsCalledByItsName(): void
    {
        $this->assertTrue($this->facade->hasUseCase('stubUseCase'));
        $this->assertCount(1, $this->facade->getUseCases());
        $this->assertSame('executed', $this->facade->stubUseCase(new StubUseCaseRequest(), new \stdClass())->action);
    }

    public function testAnUnknownUseCaseCannotBeCalled(): void
    {
        $this->expectExceptionObject(new \BadFunctionCallException('Call to undefined method Prospecter\Core\Application\UseCasesFacade::unknownUseCase()'));

        $this->facade->unknownUseCase(new StubUseCaseRequest(), new \stdClass());
    }
}
