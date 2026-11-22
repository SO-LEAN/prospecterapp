<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\ScheduledTask;
use App\Shared\ScheduledTasks;
use PHPUnit\Framework\TestCase;
use Tests\App\Double\InMemoryLogger;

/**
 * The daily schedule of an instance runs every scheduled task, one after the other, each isolated from the others.
 */
class ScheduledTasksTest extends TestCase
{
    public function testItRunsEveryTaskAndAnswersWhatEachCounted(): void
    {
        $tasks = new ScheduledTasks([self::aTask('erase-unattached', ['erased' => 2]), self::aTask('renew-gmail-watches', ['renewed' => 1, 'failed' => 0])], new InMemoryLogger());

        $this->assertSame(['ran' => ['erase-unattached' => ['erased' => 2], 'renew-gmail-watches' => ['renewed' => 1, 'failed' => 0]], 'failed' => []], $tasks->run());
    }

    public function testATaskFailingIsCountedAndTheFollowingOnesStillRun(): void
    {
        $tasks = new ScheduledTasks([self::aFailingTask('renew-gmail-watches'), self::aTask('erase-unattached', ['erased' => 2])], new InMemoryLogger());

        $this->assertSame(['ran' => ['erase-unattached' => ['erased' => 2]], 'failed' => ['renew-gmail-watches']], $tasks->run());
    }

    public function testATaskFailingIsLoggedWithItsName(): void
    {
        $logger = new InMemoryLogger();

        new ScheduledTasks([self::aFailingTask('renew-gmail-watches')], $logger)->run();

        $this->assertSame(['error', 'A scheduled task failed', 'renew-gmail-watches'], [$logger->records[0]['level'], $logger->records[0]['message'], $logger->records[0]['context']['task']]);
        $this->assertInstanceOf(\RuntimeException::class, $logger->records[0]['context']['exception']);
    }

    private static function aFailingTask(string $name): ScheduledTask
    {
        return new readonly class($name) implements ScheduledTask {
            public function __construct(private string $name)
            {
            }

            public function name(): string
            {
                return $this->name;
            }

            public function run(): array
            {
                throw new \RuntimeException('Google is unavailable');
            }
        };
    }

    /**
     * @param array<string, int> $counts
     */
    private static function aTask(string $name, array $counts): ScheduledTask
    {
        return new readonly class($name, $counts) implements ScheduledTask {
            /**
             * @param array<string, int> $counts
             */
            public function __construct(private string $name, private array $counts)
            {
            }

            public function name(): string
            {
                return $this->name;
            }

            public function run(): array
            {
                return $this->counts;
            }
        };
    }
}
