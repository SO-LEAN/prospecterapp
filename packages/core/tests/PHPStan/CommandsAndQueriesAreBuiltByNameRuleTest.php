<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetails;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\PHPStan\CommandsAndQueriesAreBuiltByNameRule;

/**
 * @extends RuleTestCase<CommandsAndQueriesAreBuiltByNameRule>
 */
final class CommandsAndQueriesAreBuiltByNameRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__.'/../../../phpstan-rules/fixtures/src/';

    protected function getRule(): Rule
    {
        return new CommandsAndQueriesAreBuiltByNameRule($this->createReflectionProvider());
    }

    public function testACommandOfMoreThanThreeArgumentsBuiltByPositionIsRefused(): void
    {
        $this->analyse([self::FIXTURES.'commands-built-by-position.php'], [
            [$this->refusalOf(ChangeContactDetails::class), 9],
            [$this->refusalOf(ChangeContactDetails::class), 10],
        ]);
    }

    public function testAQueryOfMoreThanThreeArgumentsBuiltByPositionIsRefused(): void
    {
        $this->analyse([self::FIXTURES.'queries-built-by-position.php'], [
            [$this->refusalOf(ConsultTimeline::class), 9],
        ]);
    }

    public function testACommandBuiltByNameIsAllowed(): void
    {
        $this->analyse([self::FIXTURES.'commands-built-by-name.php'], []);
    }

    public function testACommandOrAQueryOfThreeArgumentsMayBeBuiltByPosition(): void
    {
        $this->analyse([self::FIXTURES.'commands-of-three-arguments.php'], []);
    }

    public function testAClassThatIsNeitherACommandNorAQueryMayBeBuiltByPosition(): void
    {
        $this->analyse([self::FIXTURES.'other-classes-built-by-position.php'], []);
    }

    public function testATestMayBuildACommandByPosition(): void
    {
        $this->analyse([__DIR__.'/../../../phpstan-rules/fixtures/tests/commands-built-by-position.php'], []);
    }

    private function refusalOf(string $class): string
    {
        return \sprintf('%s has more than 3 arguments: name each of them, so that two of the same type cannot be swapped (docs/architecture/clean-code.md).', $class);
    }
}
