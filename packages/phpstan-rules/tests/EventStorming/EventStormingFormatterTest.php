<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\PHPStan\EventStorming\EventStormingFactsRule;
use Prospecter\PHPStan\EventStorming\EventStormingFormatter;
use Prospecter\PHPStan\EventStorming\ModelDocument;

final class EventStormingFormatterTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures';

    private const string REFUND = 'Prospecter\Core\Till\Application\Refund\Refund';

    public function testAUseCaseNoFeatureNamesFailsTheGenerationThatNamesIt(): void
    {
        $output = new WrittenOutput();
        $exit = self::formatter()->formatErrors(self::analysisOf(self::REFUND), $output);

        self::assertSame([1, '', ["No feature names these use cases:\nTill: Refund"]], [$exit, $output->raw, $output->lines]);
    }

    public function testAnAnalysisThatCouldNotReadTheCodeFailsTheGenerationThatSaysWhy(): void
    {
        $output = new WrittenOutput();
        $exit = self::formatter()->formatErrors(self::analysisOf(self::REFUND, ['A file could not be parsed']), $output);

        self::assertSame([1, '', ["The code could not be read for the event storming:\nA file could not be parsed"]], [$exit, $output->raw, $output->lines]);
    }

    private static function formatter(): EventStormingFormatter
    {
        return new EventStormingFormatter(new ModelDocument(self::FIXTURES.'/features', self::FIXTURES.'/subscriptions.json'));
    }

    /**
     * @param list<string> $failures
     */
    private static function analysisOf(string $command, array $failures = []): AnalysisResult
    {
        $handler = ['name' => $command.'Handler', 'interfaces' => [CommandHandler::class], 'parameters' => [[$command]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [], 'roles' => [], 'presents' => []];
        // @phpstan-ignore phpstanApi.constructor (the test gives the formatter what an analysis gives it)
        $facts = new Error('The facts', 'code.php', identifier: EventStormingFactsRule::IDENTIFIER, metadata: ['classes' => [$handler], 'calls' => []]);

        // @phpstan-ignore phpstanApi.constructor (the test gives the formatter what an analysis gives it)
        return new AnalysisResult([$facts], $failures, [], [], [], false, null, false, 0, false, [], false, 1);
    }
}
