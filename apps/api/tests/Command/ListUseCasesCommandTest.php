<?php

namespace Tests\App\Command;

use App\Command\ListUseCasesCommand;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Stub\StubNeedsRoleUseCase;
use Tests\App\Stub\StubPublicUseCase;

#[Group('unit')]
class ListUseCasesCommandTest extends KernelTestCase
{
    public function testEveryUseCaseIsListedWithWhoCanExecuteIt(): void
    {
        $facade = new UseCasesFacade();
        $facade->addUseCase(new StubNeedsRoleUseCase());
        $facade->addUseCase(new StubPublicUseCase());
        $application = new Application(self::bootKernel());
        $application->addCommand(new ListUseCasesCommand($facade));
        $tester = new CommandTester($application->find('app:use-cases'));

        $tester->execute([]);

        $this->assertSame([
            '2 use case(s) registered',
            '1/2 : As anonymous, I want to stub public use case',
            '2/2 : As prospector, I want to stub needs role use case',
        ], array_slice(explode("\n", $tester->getDisplay()), 0, 3));
    }
}
