<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\PHPStan\EventStorming\ModelDocument;
use Prospecter\PHPStan\EventStorming\UnreadableCode;

final class ModelDocumentTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/../../../../phpstan-rules/fixtures/event-storming';

    private const string ADD_ITEM = 'Prospecter\Core\Shop\Application\Cart\AddItem\AddItem';

    public function testTheDocumentIsTheModelOfTheContextFromTheFacts(): void
    {
        $document = (array) json_decode(self::document()->of(self::facts(), []), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(['Shop', 'command:'.self::ADD_ITEM], [$document['context'] ?? null, self::firstStickyOf($document)]);
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
        return new ModelDocument('Shop', self::FIXTURES.'/features', self::FIXTURES.'/subscriptions.json');
    }

    /**
     * @return array<string, mixed>
     */
    private static function facts(): array
    {
        $handler = ['name' => self::ADD_ITEM.'Handler', 'interfaces' => [CommandHandler::class], 'parameters' => [[self::ADD_ITEM]], 'returns' => [], 'mayReturnNothing' => false, 'dependencies' => [], 'roles' => [], 'presents' => []];

        return ['classes' => [$handler], 'calls' => []];
    }

    /**
     * @param array<mixed> $document
     */
    private static function firstStickyOf(array $document): mixed
    {
        $stickies = \is_array($document['stickies'] ?? null) ? $document['stickies'] : [];
        $first = $stickies[0] ?? null;

        return \is_array($first) ? ($first['id'] ?? null) : null;
    }
}
