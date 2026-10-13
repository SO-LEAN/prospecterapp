<?php

declare( strict_types = 1 );

namespace Tests\Core\UseCase;

use PHPUnit\Framework\Attributes\DataProvider;
use BadFunctionCallException;
use Prospecter\Core\Exception\UseCase\UnauthorizedException;
use Prospecter\Core\UseCase\AbstractUseCase;
use Prospecter\Core\UseCase\UseCaseConsumer;
use Tests\Core\Base\TestCase;
use Prospecter\Core\UseCase\UseCasesFacade;
use Tests\Core\UseCase\Stub\StubNeedsRoleUseCaseImpl;
use Tests\Core\UseCase\Stub\StubPublicUseCaseImpl;
use Tests\Core\UseCase\Stub\StubUseCaseRequest;

class UseCasesFacadeTest extends TestCase
{
    public function target() : UseCasesFacade
    {
        return parent::target();
    }

    public function initialize(): void
    {
        $this->target()->addUseCase(new StubPublicUseCaseImpl());
        $this->target()->addUseCase(new StubNeedsRoleUseCaseImpl());
    }

    public function testUseCaseCanBeAdded() : void
    {
        $this->assertTrue($this->target()->hasUseCase('stubPublicUseCase'));
    }

    public function testGetUseCases() : void
    {
        $useCases = $this->target()->getUseCases();

        $this->assertCount(2, $useCases);

        while ($useCase = array_pop($useCases)) {
            $this->assertInstanceOf(AbstractUseCase::class, $useCase);
        }
    }

    public function testMethodFromUseCaseIsCorrectlyCalled() : void
    {
        $response = $this->target()->stubPublicUseCase(new StubUseCaseRequest(), new \stdClass());
        $this->assertEquals('executed', $response->action);
    }

    public function testMethodCalledOnUnknownUseCaseThrowsABadFunctionCallException() : void
    {
        $this->expectException(BadFunctionCallException::class);
        $this->expectExceptionMessage('Prospecter\Core\UseCase\UseCasesFacade::unknownUseCase()');

        $this->target()->unknownUseCase(new StubUseCaseRequest(), new \stdClass());
    }

    public function testThrowUnauthorizedExceptionWhenConsumerHasNotAppropriateRole()
    {
        $roles = ['BAD_ROLE'];
        $this->prophesy(UseCaseConsumer::class)->getRoles()->shouldBeCalled()->willReturn($roles);

        $this->expectException(UnauthorizedException::class);

        $this->target()->stubNeedsRoleUseCase(new StubUseCaseRequest(), new \stdClass(), $this->prophesy(UseCaseConsumer::class)->reveal());
    }

        #[DataProvider('provideBadTypeParameters')]
    public function testThrowBadFunctionCallWhenBadThirdParameterProvided($msg, $consumer)
    {
        $this->expectException(BadFunctionCallException::class);
        $this->expectExceptionMessage($msg);

        $this->target()->stubPublicUseCase(new StubUseCaseRequest(), new \stdClass(), $consumer);
    }

    public static function provideBadTypeParameters()
    {
        return [
            ' - Bad class object' => ['Argument 3 passed to stubPublicUseCase must be an instance of Prospecter\Core\UseCase\UseCaseConsumer, instance of stdClass given', new \stdClass()],
            ' - Array' => ['Argument 3 passed to stubPublicUseCase must be an instance of Prospecter\Core\UseCase\UseCaseConsumer, instance of array given', []],
            ' - Boolean' => ['Argument 3 passed to stubPublicUseCase must be an instance of Prospecter\Core\UseCase\UseCaseConsumer, instance of boolean given', false],
            ' - String' => ['Argument 3 passed to stubPublicUseCase must be an instance of Prospecter\Core\UseCase\UseCaseConsumer, instance of string given', 'string'],
        ];
    }
}
