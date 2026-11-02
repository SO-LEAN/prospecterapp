<?php

declare(strict_types=1);

namespace Tests\Core\Mutation;

use Behat\Behat\ApplicationFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * The scenarios of each feature as a PHPUnit test, run by the tests of mutation only (ADR 0091): Infection knows no
 * Behat, and PHPUnit records the coverage of a test run in its process. One test per feature: Behat booted for each
 * scenario under Xdebug would take too long.
 */
final class ScenariosTest extends TestCase
{
    private const string FEATURES = __DIR__.'/../../features';

    /** @return iterable<string, array{string}> */
    public static function features(): iterable
    {
        foreach (glob(self::FEATURES.'/*.feature') as $feature) {
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
     * Behat sets its own error handlers and keeps them: they are removed down to the one PHPUnit watches.
     */
    private function runBehat(string $feature, BufferedOutput $output): int
    {
        $errorHandlerOfPhpunit = get_error_handler();
        $behat = new ApplicationFactory()->createApplication();
        $behat->setAutoExit(false);

        $status = $behat->run(new ArrayInput(['paths' => [$feature], ...self::behatOptions()]), $output);

        while (get_error_handler() !== $errorHandlerOfPhpunit) {
            restore_error_handler();
        }

        return $status;
    }

    /**
     * Without --xdebug, Behat restarts PHP without Xdebug, and the coverage of the test is lost.
     *
     * @return array<string, mixed>
     */
    private static function behatOptions(): array
    {
        return ['--strict' => true, '--format' => ['progress'], '--no-colors' => true, '--xdebug' => true];
    }
}
