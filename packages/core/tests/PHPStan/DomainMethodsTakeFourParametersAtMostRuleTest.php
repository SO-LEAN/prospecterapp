<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Prospecter\PHPStan\DomainMethodsTakeFourParametersAtMostRule;

/**
 * @extends RuleTestCase<DomainMethodsTakeFourParametersAtMostRule>
 */
final class DomainMethodsTakeFourParametersAtMostRuleTest extends RuleTestCase
{
    private const string REFUSAL = 'A public method of the domain takes four parameters at most: group those that go together in an argument object (docs/architecture/clean-code.md).';

    private const string DOMAIN_MODEL = __DIR__.'/../../../phpstan-rules/fixtures/src/Domain/Model/parameters-of-domain-methods.php';

    protected function getRule(): Rule
    {
        return new DomainMethodsTakeFourParametersAtMostRule();
    }

    public function testAPublicMethodOfTheDomainTakingMoreThanFourParametersIsRefused(): void
    {
        $this->analyse([self::DOMAIN_MODEL], [[self::REFUSAL, 7]]);
    }

    public function testAMethodOutOfTheDomainModelMayTakeMore(): void
    {
        $this->analyse([__DIR__.'/../../../phpstan-rules/fixtures/src/parameters-out-of-the-domain.php'], []);
    }
}
