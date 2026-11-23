<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use PHPUnit\Framework\TestCase;

/**
 * Runs a rule of packages/phpstan-rules on a small project, with the result cache that make lint keeps from one run
 * to the next. A rule that reads a class the analysed file does not name must declare it to that cache. Otherwise
 * the file keeps its former result when that class changes, while a fresh analysis, as the CI runs it, differs.
 */
abstract class ResultCacheTestCase extends TestCase
{
    private const string PHPSTAN = __DIR__.'/../../vendor/bin/phpstan';

    private string $project;

    /**
     * @return class-string the rule under test
     */
    abstract protected function rule(): string;

    /**
     * @return string the folder of the project's fixtures: its src/ is analysed
     */
    abstract protected function fixtures(): string;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir().'/phpstan-cache-'.bin2hex(random_bytes(4));
        mkdir($this->project.'/src', recursive: true);
        array_map($this->copyToTheProject(...), glob($this->fixtures().'src/*.php') ?: []);
        file_put_contents($this->project.'/phpstan.neon', $this->configuration());
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->project));
    }

    /**
     * Writes a fixture outside src/ as a file of the project.
     */
    protected function write(string $fixture, string $asFile): void
    {
        copy($this->fixtures().$fixture, $this->project.'/src/'.$asFile);
    }

    /**
     * @return list<string> the files PHPStan refuses
     */
    protected function analyse(): array
    {
        exec(\sprintf('php %s analyse --configuration=%s --no-progress --error-format=json 2>/dev/null', escapeshellarg(self::PHPSTAN), escapeshellarg($this->project.'/phpstan.neon')), $output);
        $result = json_decode(implode('', $output), true, flags: \JSON_THROW_ON_ERROR);
        \assert(\is_array($result) && \is_array($result['files']));

        return array_map(basename(...), array_keys($result['files']));
    }

    private function copyToTheProject(string $fixture): void
    {
        copy($fixture, $this->project.'/src/'.basename($fixture));
    }

    private function configuration(): string
    {
        return <<<NEON
            rules:
                - {$this->rule()}
            parameters:
                level: 0
                tmpDir: {$this->project}/cache
                paths:
                    - {$this->project}/src
                parallel:
                    maximumNumberOfProcesses: 1
            NEON;
    }
}
