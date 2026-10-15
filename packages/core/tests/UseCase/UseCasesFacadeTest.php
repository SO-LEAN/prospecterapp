<?php

namespace Tests\Core\UseCase;

use BadFunctionCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Exception\UseCase\UnauthorizedException;
use Prospecter\Core\UseCase\UseCasesFacade;
use stdClass;
use Tests\Core\Double\SignedInUser;
use Tests\Core\UseCase\Stub\StubNeedsRoleUseCaseImpl;
use Tests\Core\UseCase\Stub\StubPublicUseCaseImpl;
use Tests\Core\UseCase\Stub\StubUseCaseRequest;

/**
 * The facade is how the API calls the use cases today, and where the roles of a use case are enforced.
 */
class UseCasesFacadeTest extends TestCase
{
    private UseCasesFacade $facade;

    protected function setUp(): void
    {
        $this->facade = new UseCasesFacade();
        $this->facade->addUseCase(new StubPublicUseCaseImpl());
        $this->facade->addUseCase(new StubNeedsRoleUseCaseImpl());
    }

    public function testUseCasesAreCalledByTheirNameWithoutImplSuffix(): void
    {
        $this->assertTrue($this->facade->hasUseCase('stubPublicUseCase'));
        $this->assertCount(2, $this->facade->getUseCases());
        $this->assertSame('executed', $this->facade->stubPublicUseCase(new StubUseCaseRequest(), new stdClass())->action);
    }

    public function testAnUnknownUseCaseCannotBeCalled(): void
    {
        $this->expectExceptionObject(new BadFunctionCallException('Call to undefined method Prospecter\Core\UseCase\UseCasesFacade::unknownUseCase()'));

        $this->facade->unknownUseCase(new StubUseCaseRequest(), new stdClass());
    }

    public function testAPublicUseCaseIsOpenToAnonymousUsers(): void
    {
        $this->assertSame('executed', $this->facade->stubPublicUseCase(new StubUseCaseRequest(), new stdClass())->action);
    }

    public function testAProtectedUseCaseIsRefusedToAnonymousUsers(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new stdClass());
    }

    public function testAProtectedUseCaseIsRefusedWithoutItsRole(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new stdClass(), $this->userWith(['ROLE_USER']));
    }

    public function testAProtectedUseCaseIsOpenWithItsRole(): void
    {
        $this->assertSame('executed', $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new stdClass(), $this->userWith(['SPECIFIC_ROLE']))->action);
    }

    public function testAnAdministratorCanCallEveryUseCase(): void
    {
        $this->assertSame('executed', $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new stdClass(), $this->userWith(['ROLE_ADMIN']))->action);
    }

    #[DataProvider('notAConsumer')]
    public function testTheThirdArgumentMustBeTheConsumer(string $message, mixed $consumer): void
    {
        $this->expectExceptionObject(new BadFunctionCallException($message));

        $this->facade->stubPublicUseCase(new StubUseCaseRequest(), new stdClass(), $consumer);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function notAConsumer(): iterable
    {
        $expected = 'Argument 3 passed to stubPublicUseCase must be an instance of Prospecter\Core\UseCase\UseCaseConsumer, instance of %s given';

        yield 'another object' => [sprintf($expected, 'stdClass'), new stdClass()];
        yield 'an array' => [sprintf($expected, 'array'), []];
        yield 'a string' => [sprintf($expected, 'string'), 'string'];
    }

    /**
     * @param list<string> $roles
     */
    private function userWith(array $roles): SignedInUser
    {
        return new SignedInUser(1, 1, $roles);
    }
}
