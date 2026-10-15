<?php

declare(strict_types=1);

namespace Tests\Core\UseCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Domain\User\Role;
use Tests\Core\UseCase\Stub\StubPublicUseCase;

class UseCaseTest extends TestCase
{
    /**
     * @param list<Role> $roles
     */
    #[DataProvider('useCases')]
    public function testAUseCaseDescribesWhoWantsWhat(string $expected, array $roles): void
    {
        $this->assertSame($expected, (string) new StubPublicUseCase($roles));
    }

    /**
     * @return iterable<string, array{string, list<Role>}>
     */
    public static function useCases(): iterable
    {
        yield 'anonymous' => ['As anonymous, I want to stub public use case', []];
        yield 'one role' => ['As prospector, I want to stub public use case', [Role::Prospector]];
        yield 'several roles' => ['As user, prospector or admin, I want to stub public use case', [Role::User, Role::Prospector, Role::Admin]];
    }
}
