<?php

declare(strict_types=1);

namespace Tests\Core\Mutation;

use Behat\Behat\ApplicationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Each feature's scenarios as a PHPUnit test. Only the mutation tests run it (ADR 0091): Infection does not know
 * Behat, and PHPUnit records the coverage of a test that runs in its process. There is one test per feature,
 * because booting Behat for each scenario under Xdebug would take too long.
 */
final class ScenariosTest extends TestCase
{
    private const string FEATURES = __DIR__.'/../../features';

    /** @return iterable<string, array{string}> */
    public static function features(): iterable
    {
        foreach (glob(self::FEATURES.'/*.feature') ?: [] as $feature) {
            yield basename($feature) => [$feature];
        }
    }

    #[DataProvider('features')]
    public function testTheScenariosOfTheFeaturePass(string $feature): void
    {
        $output = new BufferedOutput();

        $status = $this->runBehat($feature, $output);

        self::assertSame(0, $status, $output->fetch());
    }

    /**
     * Behat sets its own error handlers and keeps them. They are removed until the handler that PHPUnit watches is
     * back on top.
     */
    private function runBehat(string $feature, BufferedOutput $output): int
    {
        $errorHandlerOfPhpunit = get_error_handler();
        $behat = new ApplicationFactory()->createApplication();
        $behat->setAutoExit(false);

        $status = $behat->run(new ArrayInput(['paths' => [$feature], ...self::behatOptions()]), $output);

        while (!\in_array(get_error_handler(), [$errorHandlerOfPhpunit, null], true)) {
            restore_error_handler();
        }

        return $status;
    }

    /**
     * Without --xdebug, Behat restarts PHP without Xdebug, and the test's coverage is lost.
     *
     * @return array<string, mixed>
     */
    private static function behatOptions(): array
    {
        return ['--strict' => true, '--format' => ['progress'], '--no-colors' => true, '--xdebug' => true];
    }
}
