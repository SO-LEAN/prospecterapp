<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\Shop\Application\Cart\AddItem\AddItem;
use Prospecter\PHPStan\EventStorming\ModelDocument;
use Prospecter\PHPStan\EventStorming\UnreadableCode;
use Prospecter\PHPStan\EventStorming\UntitledUseCases;

final class ModelDocumentTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/Fixtures';

    private const string ADD_ITEM = AddItem::class;

    private const string PAY = 'Prospecter\Core\Till\Application\Pay\Pay';

    private const string REFUND = 'Prospecter\Core\Till\Application\Refund\Refund';

    private const string EXCHANGE = 'Prospecter\Core\Till\Application\Exchange\Exchange';

    private const string TIMELINE_MODULE = 'Prospecter\Core\Till\Application\Timeline\\';

    public function testTheDocumentHoldsTheModelOfEachContextOfTheFacts(): void
    {
        $document = (array) json_decode(self::document()->of(self::facts(), []), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame([['Shop', 'command:'.self::ADD_ITEM], ['Till', 'command:'.self::PAY]], self::contextsAndFirstStickiesOf($document));
    }

    public function testTwoViewsSharingAPresenterAreEachNamedAfterTheirQuery(): void
    {
        $document = (array) json_decode(self::viewsDocument()->of(self::viewsOf('ConsultTimeline', 'ConsultUpcomingExchanges'), []), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(['Timeline', 'Upcoming exchanges'], self::readModelLabelsOf($document));
    }

    public function testAUseCaseNoFeatureNamesGivesNoDocumentAndIsNamed(): void
    {
        $this->expectExceptionObject(UntitledUseCases::named(['Till: Refund']));

        self::document()->of(self::facts(self::REFUND), []);
    }

    public function testTheRefusalNamesEveryUseCaseNoFeatureNames(): void
    {
        $this->expectExceptionMessageIsOrContains("No feature names these use cases:\nTill: Exchange\nTill: Refund");

        self::document()->of(self::facts(self::REFUND, self::EXCHANGE), []);
    }

    public function testTheDocumentEndsItsLastLine(): void
    {
        self::assertStringEndsWith("}\n", self::document()->of(self::facts(), []));
    }

    public function testTheDocumentWarnsOfEachFeatureThatNamesNoUseCase(): void
    {
        self::assertContains('The feature payer.feature names no use case in its tags', self::warnings());
    }

    /**
     * @return array<mixed> the warnings the document of the facts holds
     */
    private static function warnings(): array
    {
        $document = (array) json_decode(self::document()->of(self::facts(), []), true, flags: \JSON_THROW_ON_ERROR);

        return \is_array($document['warnings'] ?? null) ? $document['warnings'] : [];
    }

    public function testAnAnalysisThatReadNoFactsGivesNoDocument(): void
    {
        $this->expectException(UnreadableCode::class);

        self::document()->of(null, []);
    }

    public function testAnAnalysisThatCouldNotReadTheCodeGivesNoDocument(): void
    {
        $this->expectExceptionObject(UnreadableCode::because(['A file could not be parsed']));

        self::document()->of(self::facts(), ['A file could not be parsed']);
    }

    private static function document(): ModelDocument
    {
        return new ModelDocument(self::FIXTURES.'/features', self::FIXTURES.'/subscriptions.json');
    }

    private static function viewsDocument(): ModelDocument
    {
        return new ModelDocument(self::FIXTURES.'/views', self::FIXTURES.'/subscriptions.json');
    }

    /**
     * @return array<string, mixed> the facts of a query handler for each query, all presenting the timeline
     */
    private static function viewsOf(string ...$queries): array
    {
        return ['classes' => array_map(static fn (string $query): array => [
            'name' => self::TIMELINE_MODULE.$query.'Handler', 'interfaces' => [QueryHandler::class], 'parameters' => [[self::TIMELINE_MODULE.$query]],
            'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [], 'roles' => [], 'presents' => [self::TIMELINE_MODULE.'Timeline'],
        ], $queries), 'calls' => []];
    }

    /**
     * @param array<mixed> $document
     *
     * @return list<mixed> the label of each read model of the document's models
     */
    private static function readModelLabelsOf(array $document): array
    {
        $labels = [];
        foreach (\is_array($document['models'] ?? null) ? $document['models'] : [] as $model) {
            foreach (\is_array($model) && \is_array($model['stickies'] ?? null) ? $model['stickies'] : [] as $sticky) {
                if (\is_array($sticky) && 'readModel' === ($sticky['type'] ?? null)) {
                    $labels[] = $sticky['label'] ?? null;
                }
            }
        }

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private static function facts(string ...$untitled): array
    {
        return ['classes' => array_map(self::handlerOf(...), [self::PAY, self::ADD_ITEM, ...$untitled]), 'calls' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private static function handlerOf(string $command): array
    {
        return ['name' => $command.'Handler', 'interfaces' => [CommandHandler::class], 'parameters' => [[$command]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [], 'roles' => [], 'presents' => []];
    }

    /**
     * @param array<mixed> $document
     *
     * @return list<array{mixed, mixed}>
     */
    private static function contextsAndFirstStickiesOf(array $document): array
    {
        $models = \is_array($document['models'] ?? null) ? array_values(array_filter($document['models'], \is_array(...))) : [];

        return array_map(static function (array $model): array {
            $stickies = \is_array($model['stickies'] ?? null) ? $model['stickies'] : [];
            $first = $stickies[0] ?? null;

            return [$model['context'] ?? null, \is_array($first) ? ($first['id'] ?? null) : null];
        }, $models);
    }
}
