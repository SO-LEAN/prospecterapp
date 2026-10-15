<?php

namespace Tests\App\Command;

use App\Command\ListUseCasesCommand;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Stub\StubFirstUseCase;
use Tests\App\Stub\StubSecondUseCase;

#[Group('unit')]
class ListUseCasesCommandTest extends KernelTestCase
{
    public function testEveryUseCaseIsListedWithTheIntentItServes(): void
    {
        $facade = new UseCasesFacade();
        $facade->addUseCase(new StubSecondUseCase());
        $facade->addUseCase(new StubFirstUseCase());
        $application = new Application(self::bootKernel());
        $application->addCommand(new ListUseCasesCommand($facade));
        $tester = new CommandTester($application->find('app:use-cases'));

        $tester->execute([]);

        $this->assertSame([
            '2 use case(s) registered',
            '1/2 : I want to stub first use case',
            '2/2 : I want to stub second use case',
        ], array_slice(explode("\n", $tester->getDisplay()), 0, 3));
    }
}
