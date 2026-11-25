<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
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

    public function testTheDocumentHoldsTheModelOfEachContextOfTheFacts(): void
    {
        $document = (array) json_decode(self::document()->of(self::facts(), []), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame([['Shop', 'command:'.self::ADD_ITEM], ['Till', 'command:'.self::PAY]], self::contextsAndFirstStickiesOf($document));
    }

    public function testAUseCaseNoFeatureNamesGivesNoDocumentAndIsNamed(): void
    {
        $this->expectExceptionObject(UntitledUseCases::named(['Till: Refund']));

        self::document()->of(self::facts(self::REFUND), []);
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
