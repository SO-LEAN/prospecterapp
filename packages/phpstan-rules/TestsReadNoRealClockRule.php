<?php

declare(strict_types=1);

namespace Prospecter\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * No test depends on the real date (ADR 0146): a test gives what it tests a fixed moment, so that it passes the
 * same today, tomorrow and in a year. The code of the applications reads its clock; a test that cannot do
 * otherwise (the tokens Cognito issues on the real clock) names its exception: `@phpstan-ignore
 * prospecter.realClock (<why>)`.
 *
 * @implements Rule<CallLike>
 */
final class TestsReadNoRealClockRule implements Rule
{
    private const string MESSAGE = 'A test reads no real clock: give it a fixed moment (docs/architecture/tests.md).';

    /** The classes building a moment from the clock without one, or from one relative to now. */
    private const array MOMENTS = ['datetimeimmutable', 'datetime', 'symfony\\component\\clock\\mockclock'];

    private const array CLOCKS = ['symfony\\component\\clock\\nativeclock'];

    /** The functions reading the clock, by the number of arguments that spares it. */
    private const array READING_FUNCTIONS = [
        'time' => \PHP_INT_MAX,
        'microtime' => \PHP_INT_MAX,
        'symfony\\component\\clock\\now' => \PHP_INT_MAX,
        'date' => 2,
        'gmdate' => 2,
        'idate' => 2,
        'mktime' => 6,
        'gmmktime' => 6,
    ];

    /** The functions building a moment from the one they are given, from the clock otherwise. */
    private const array MOMENT_FUNCTIONS = ['date_create', 'date_create_immutable', 'strtotime'];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isATest($scope) || !$this->readsTheRealClock($node, $scope)) {
            return [];
        }

        return [RuleErrorBuilder::message(self::MESSAGE)->identifier('prospecter.realClock')->build()];
    }

    private function isATest(Scope $scope): bool
    {
        return str_contains($scope->getFile(), '/tests/');
    }

    private function readsTheRealClock(CallLike $call, Scope $scope): bool
    {
        if ($call->isFirstClassCallable()) {
            return false;
        }

        return match (true) {
            $call instanceof New_ && $call->class instanceof Name => $this->buildsOnTheClock(strtolower($scope->resolveName($call->class)), $call->getArgs()),
            $call instanceof FuncCall && $call->name instanceof Name => $this->callsTheClock($call->name->toLowerString(), $call->getArgs()),
            $call instanceof StaticCall => $this->asksTheGlobalClock($call, $scope),
            default => false,
        };
    }

    /**
     * @param list<Arg> $arguments
     */
    private function buildsOnTheClock(string $class, array $arguments): bool
    {
        return \in_array($class, self::CLOCKS, true) || (\in_array($class, self::MOMENTS, true) && !$this->namesAMoment($arguments));
    }

    /**
     * @param list<Arg> $arguments
     */
    private function callsTheClock(string $function, array $arguments): bool
    {
        if (\in_array($function, self::MOMENT_FUNCTIONS, true)) {
            return !$this->namesAMoment($arguments) && !isset($arguments[1]);
        }

        return \count($arguments) < (self::READING_FUNCTIONS[$function] ?? 0);
    }

    private function asksTheGlobalClock(StaticCall $call, Scope $scope): bool
    {
        return $call->class instanceof Name
            && $call->name instanceof Identifier
            && 'symfony\\component\\clock\\clock' === strtolower($scope->resolveName($call->class))
            && 'get' === $call->name->toLowerString();
    }

    /**
     * A moment a test cannot see is one it was given (a variable, a constant): only a literal relative to now
     * (`'now'`, `'+1 hour'`, `'tomorrow'`) reads the clock.
     *
     * @param list<Arg> $arguments
     */
    private function namesAMoment(array $arguments): bool
    {
        $moment = $arguments[0]->value ?? null;

        return null !== $moment && (!$moment instanceof String_ || $this->isAbsolute($moment->value));
    }

    private function isAbsolute(string $moment): bool
    {
        return str_starts_with($moment, '@') || false !== date_parse($moment)['year'];
    }
}
