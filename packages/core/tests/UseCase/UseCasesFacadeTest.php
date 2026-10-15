<?php

declare(strict_types=1);

namespace Tests\Core\UseCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Application\Exception\UnauthorizedException;
use Prospecter\Core\Application\UseCasesFacade;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\UserId;
use Tests\Core\Double\SignedInUser;
use Tests\Core\UseCase\Stub\StubNeedsRoleUseCase;
use Tests\Core\UseCase\Stub\StubPublicUseCase;
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
        $this->facade->addUseCase(new StubPublicUseCase());
        $this->facade->addUseCase(new StubNeedsRoleUseCase());
    }

    public function testUseCasesAreCalledByTheirNameWithoutImplSuffix(): void
    {
        $this->assertTrue($this->facade->hasUseCase('stubPublicUseCase'));
        $this->assertCount(2, $this->facade->getUseCases());
        $this->assertSame('executed', $this->facade->stubPublicUseCase(new StubUseCaseRequest(), new \stdClass())->action);
    }

    public function testAnUnknownUseCaseCannotBeCalled(): void
    {
        $this->expectExceptionObject(new \BadFunctionCallException('Call to undefined method Prospecter\Core\Application\UseCasesFacade::unknownUseCase()'));

        $this->facade->unknownUseCase(new StubUseCaseRequest(), new \stdClass());
    }

    public function testAPublicUseCaseIsOpenToAnonymousUsers(): void
    {
        $this->assertSame('executed', $this->facade->stubPublicUseCase(new StubUseCaseRequest(), new \stdClass())->action);
    }

    public function testAProtectedUseCaseIsRefusedToAnonymousUsers(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new \stdClass());
    }

    public function testAProtectedUseCaseIsRefusedWithoutItsRole(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new \stdClass(), $this->userWith(Role::User));
    }

    public function testAProtectedUseCaseIsOpenWithItsRole(): void
    {
        $this->assertSame('executed', $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new \stdClass(), $this->userWith(Role::Prospector))->action);
    }

    public function testAnAdministratorCanCallEveryUseCase(): void
    {
        $this->assertSame('executed', $this->facade->stubNeedsRoleUseCase(new StubUseCaseRequest(), new \stdClass(), $this->userWith(Role::Admin))->action);
    }

    #[DataProvider('notAConsumer')]
    public function testTheThirdArgumentMustBeTheConsumer(string $message, mixed $consumer): void
    {
        $this->expectExceptionObject(new \BadFunctionCallException($message));

        $this->facade->stubPublicUseCase(new StubUseCaseRequest(), new \stdClass(), $consumer);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function notAConsumer(): iterable
    {
        $expected = 'Argument 3 passed to stubPublicUseCase must be an instance of Prospecter\Core\Application\UseCaseConsumer, instance of %s given';

        yield 'another object' => [sprintf($expected, 'stdClass'), new \stdClass()];
        yield 'an array' => [sprintf($expected, 'array'), []];
        yield 'a string' => [sprintf($expected, 'string'), 'string'];
    }

    private function userWith(Role $role): SignedInUser
    {
        return new SignedInUser(new UserId('0190a1c0-0000-7000-8000-000000000001'), new OrganizationId('0190a1c0-0000-7000-8000-000000000002'), $role);
    }
}
