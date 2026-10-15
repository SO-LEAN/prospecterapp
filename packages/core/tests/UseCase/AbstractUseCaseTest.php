<?php

namespace Tests\Core\UseCase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Core\UseCase\Stub\StubPublicUseCaseImpl;

class AbstractUseCaseTest extends TestCase
{
    #[DataProvider('provideUseCases')]
    public function testUseCaseAsString(string $expected, array $roles)
    {
        $useCase = new StubPublicUseCaseImpl($roles);

        $this->assertEquals($expected, (string) $useCase);
    }

    public static function provideUseCases()
    {
        (yield 'anonymous' => ['As anonymous, I want to stub public use case', []]);
        (yield 'one role' => ['As developer, I want to stub public use case', ['ROLE_DEVELOPER']]);
        (yield 'multirole' => ['As developer, project_manager or scrum_master, I want to stub public use case', ['ROLE_DEVELOPER', 'ROLE_PROJECT_MANAGER', 'ROLE_SCRUM_MASTER']]);
    }
}
