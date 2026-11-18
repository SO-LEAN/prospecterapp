<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Prospecter\PHPStan\TestsReadNoRealClockRule;

/**
 * @extends RuleTestCase<TestsReadNoRealClockRule>
 */
final class TestsReadNoRealClockRuleTest extends RuleTestCase
{
    private const string REFUSAL = 'A test reads no real clock: give it a fixed moment (docs/architecture/tests.md).';

    protected function getRule(): Rule
    {
        return new TestsReadNoRealClockRule();
    }

    public function testATestReadingTheRealClockIsRefusedWhereItReadsIt(): void
    {
        $this->analyse([__DIR__.'/../../../phpstan-rules/fixtures/tests/reads-of-the-clock.php'], array_map(
            static fn (int $line): array => [self::REFUSAL, $line],
            range(11, 30),
        ));
    }

    public function testTheCodeOfTheApplicationMayReadTheRealClock(): void
    {
        $this->analyse([__DIR__.'/../../../phpstan-rules/fixtures/src/reads-of-the-clock.php'], []);
    }
}
