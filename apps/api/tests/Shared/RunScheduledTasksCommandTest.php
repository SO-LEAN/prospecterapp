<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Command\RunScheduledTasksCommand;
use App\Shared\ScheduledTask;
use App\Shared\ScheduledTasks;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\InMemoryLogger;

/**
 * `app:scheduled-tasks:run` runs every scheduled task, as the daily schedule of an instance does: on the local stack,
 * whose MiniStack runs no schedule, or on an instance whose schedule missed a day.
 */
class RunScheduledTasksCommandTest extends ApiTestCase
{
    public function testTheConsoleRunsEveryScheduledTaskAndSaysWhatEachCounted(): void
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:scheduled-tasks:run'));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('erase-unattached: erased 0', $tester->getDisplay());
        $this->assertStringContainsString('renew-gmail-watches: renewed 0, failed 0', $tester->getDisplay());
    }

    public function testATaskFailingIsNamedAndTheConsoleFails(): void
    {
        $tester = new CommandTester(new Command(null, new RunScheduledTasksCommand(new ScheduledTasks([self::aFailingTask()], new InMemoryLogger()))));

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('renew-gmail-watches failed', $tester->getDisplay());
    }

    private static function aFailingTask(): ScheduledTask
    {
        return new readonly class implements ScheduledTask {
            public function name(): string
            {
                return 'renew-gmail-watches';
            }

            public function run(): array
            {
                throw new \RuntimeException('Google is unavailable');
            }
        };
    }
}
