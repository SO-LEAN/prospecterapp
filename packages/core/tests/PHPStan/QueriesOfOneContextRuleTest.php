<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Prospecter\PHPStan\QueriesOfOneContextRule;

/**
 * @extends RuleTestCase<QueriesOfOneContextRule>
 */
final class QueriesOfOneContextRuleTest extends RuleTestCase
{
    private const string FIXTURES = __DIR__.'/../../../phpstan-rules/fixtures/src/queries-of-contexts/';

    protected function getRule(): Rule
    {
        return new QueriesOfOneContextRule($this->createReflectionProvider());
    }

    public function testAClassAskingQueriesOfTwoContextsIsRefused(): void
    {
        $this->analyse([self::FIXTURES.'two-contexts.php'], [
            ['App\Fixtures\AsksTwoContexts asks queries of PersonalData, Prospecting: only a *Composition joins several contexts, and an anticorruption adapter asks one (ADR 0223).', 10],
        ]);
    }

    public function testAClassMayAskSeveralQueriesOfOneContext(): void
    {
        $this->analyse([self::FIXTURES.'one-context.php'], []);
    }

    public function testACompositionMayAskQueriesOfSeveralContexts(): void
    {
        $this->analyse([self::FIXTURES.'composition.php'], []);
    }

    public function testTheContactRecorderMayAskQueriesOfSeveralContexts(): void
    {
        $this->analyse([self::FIXTURES.'contact-recorder.php'], []);
    }

    public function testATestMayAskQueriesOfSeveralContexts(): void
    {
        $this->analyse([__DIR__.'/../../../phpstan-rules/fixtures/tests/queries-of-two-contexts.php'], []);
    }
}
