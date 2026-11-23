<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use Prospecter\PHPStan\CommandsAndQueriesAreBuiltByNameRule;

/**
 * Whether the rule refuses a file depends on the command's handler, a class that the file does not name.
 */
final class CommandsAndQueriesAreBuiltByNameRuleCacheTest extends ResultCacheTestCase
{
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

    protected function rule(): string
    {
        return CommandsAndQueriesAreBuiltByNameRule::class;
    }

    protected function fixtures(): string
    {
        return __DIR__.'/../../../phpstan-rules/fixtures/cache-of-commands/';
    }

    private function giveTheHandler(string $fixture): void
    {
        $this->write('handlers/'.$fixture, 'SendInvitationHandler.php');
    }
}
