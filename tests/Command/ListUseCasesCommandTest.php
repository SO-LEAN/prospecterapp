<?php

namespace Tests\App\Command;

use App\Command\ListUseCasesCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Stub\StubPublicUseCaseImpl;
use Tests\App\Stub\StubNeedsRoleUseCaseImpl;
use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group unit
 */
class ListUseCasesCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $facade = $this
            ->prophesize(UseCasesFacade::class);
        $facade
            ->getUseCases()
            ->shouldbeCalled()
            ->willReturn([new StubNeedsRoleUseCaseImpl(), new StubPublicUseCaseImpl()]);

        $application->add(new ListUseCasesCommand($facade->reveal()));
        $command = $application->find('app:use-cases');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['command'  => $command->getName()]);
        $lines = explode("\n", $commandTester->getDisplay());

        $this->assertEquals('2 use case(s) registered', $lines[0]);
        $this->assertEquals('1/2 : As anonymous, I want to stub public use case', $lines[1]);
        $this->assertEquals('2/2 : As specific_role, I want to stub needs role use case', $lines[2]);

    }
}
