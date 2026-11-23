<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use PHPUnit\Framework\TestCase;

/**
 * Whether the rule refuses a file depends on a handler that the file does not name. The result cache of PHPStan,
 * which make lint keeps from one run to the next, must analyse the file again when that handler changes: a fresh
 * analysis, as the CI runs it, would refuse the file.
 */
final class CommandsAndQueriesAreBuiltByNameRuleCacheTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/../../../phpstan-rules/fixtures/cache/';

    private const string PHPSTAN = __DIR__.'/../../vendor/bin/phpstan';

    private string $project;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir().'/phpstan-cache-'.bin2hex(random_bytes(4));
        mkdir($this->project.'/src', recursive: true);
        array_map($this->copyToTheProject(...), glob(self::FIXTURES.'src/*.php') ?: []);
        file_put_contents($this->project.'/phpstan.neon', $this->configuration());
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->project));
    }

    public function testAFileBuildingACommandByPositionIsAnalysedAgainOnceItsHandlerBecomesACommandHandler(): void
    {
        $this->giveTheHandler('event-handler.php');
        $this->assertSame([], $this->analyse());

        $this->giveTheHandler('command-handler.php');

        $this->assertSame(['InvitationSender.php'], $this->analyse());
    }

    public function testAFileBuildingACommandByPositionIsAnalysedAgainOnceItsCommandHandlerIsWritten(): void
    {
        $this->assertSame([], $this->analyse());

        $this->giveTheHandler('command-handler.php');

        $this->assertSame(['InvitationSender.php'], $this->analyse());
    }

    private function copyToTheProject(string $fixture): void
    {
        copy($fixture, $this->project.'/src/'.basename($fixture));
    }

    private function giveTheHandler(string $fixture): void
    {
        copy(self::FIXTURES.'handlers/'.$fixture, $this->project.'/src/SendInvitationHandler.php');
    }

    private function configuration(): string
    {
        return <<<NEON
            rules:
                - Prospecter\\PHPStan\\CommandsAndQueriesAreBuiltByNameRule
            parameters:
                level: 0
                tmpDir: {$this->project}/cache
                paths:
                    - {$this->project}/src
                parallel:
                    maximumNumberOfProcesses: 1
            NEON;
    }

    /**
     * @return list<string> the files PHPStan refuses
     */
    private function analyse(): array
    {
        exec(\sprintf('php %s analyse --configuration=%s --no-progress --error-format=json 2>/dev/null', escapeshellarg(self::PHPSTAN), escapeshellarg($this->project.'/phpstan.neon')), $output);
        $result = json_decode(implode('', $output), true, flags: \JSON_THROW_ON_ERROR);
        \assert(\is_array($result) && \is_array($result['files']));

        return array_map(basename(...), array_keys($result['files']));
    }
}
