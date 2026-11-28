<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\PHPStan\EventStorming\EventStormingFactsRule;
use Prospecter\PHPStan\EventStorming\EventStormingFormatter;
use Prospecter\PHPStan\EventStorming\ModelDocument;

final class EventStormingFormatterTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures';

    private const string REFUND = 'Prospecter\Core\Till\Application\Refund\Refund';

    private const string TIMELINE_MODULE = 'Prospecter\Core\Till\Application\Timeline\\';

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

    public function testViewsOfAContextThatTheirQueriesCannotNameApartFailTheGenerationThatNamesThem(): void
    {
        $output = new WrittenOutput();
        $exit = self::formatter()->formatErrors(self::analysisOfViews(['ConsultTimeline' => 'Agenda', 'ConsultPlanning' => 'Agenda', 'ConsultHistory' => 'Timeline']), $output);

        self::assertSame([1, '', ["These queries of one context name their views alike:\nTill: ConsultHistory, ConsultTimeline"]], [$exit, $output->raw, $output->lines]);
    }

    private static function formatter(): EventStormingFormatter
    {
        return new EventStormingFormatter(new ModelDocument(self::FIXTURES.'/features', self::FIXTURES.'/subscriptions.json'));
    }

    /**
     * @param array<string, string> $presenters the presenter of each query, by query
     */
    private static function analysisOfViews(array $presenters): AnalysisResult
    {
        $handlers = array_map(
            static fn (string $query, string $presenter): array => ['name' => self::TIMELINE_MODULE.$query.'Handler', 'interfaces' => [QueryHandler::class], 'parameters' => [[self::TIMELINE_MODULE.$query]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [], 'roles' => [], 'presents' => [self::TIMELINE_MODULE.$presenter]],
            array_keys($presenters),
            $presenters,
        );

        return self::analysisOfClasses($handlers, []);
    }

    /**
     * @param list<string> $failures
     */
    private static function analysisOf(string $command, array $failures = []): AnalysisResult
    {
        $handler = ['name' => $command.'Handler', 'interfaces' => [CommandHandler::class], 'parameters' => [[$command]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [], 'roles' => [], 'presents' => []];

        return self::analysisOfClasses([$handler], $failures);
    }

    /**
     * @param list<array<string, mixed>> $classes
     * @param list<string>               $failures
     */
    private static function analysisOfClasses(array $classes, array $failures): AnalysisResult
    {
        // @phpstan-ignore phpstanApi.constructor (the test gives the formatter what an analysis gives it)
        $facts = new Error('The facts', 'code.php', identifier: EventStormingFactsRule::IDENTIFIER, metadata: ['classes' => $classes, 'calls' => []]);

        // @phpstan-ignore phpstanApi.constructor (the test gives the formatter what an analysis gives it)
        return new AnalysisResult([$facts], $failures, [], [], [], false, null, false, 0, false, [], false, 1);
    }
}
