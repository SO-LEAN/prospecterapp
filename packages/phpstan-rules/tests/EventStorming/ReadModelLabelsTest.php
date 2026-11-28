<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\PHPStan\EventStorming\Elsewhere;
use Prospecter\PHPStan\EventStorming\Model;
use Prospecter\PHPStan\EventStorming\ReadModelLabels;
use Prospecter\PHPStan\EventStorming\SharedReadModelLabels;
use Prospecter\PHPStan\EventStorming\Sticky;
use Prospecter\PHPStan\EventStorming\StickyType;

final class ReadModelLabelsTest extends TestCase
{
    public function testTwoViewsSharingALabelAreEachNamedAfterTheirQuery(): void
    {
        $models = ReadModelLabels::distinct([self::modelOf('Till', self::readModel('Till', 'ConsultTimeline', 'Timeline'), self::readModel('Till', 'ConsultUpcomingExchanges', 'Timeline'))]);

        self::assertSame(['Timeline', 'Upcoming exchanges'], self::labelsOf($models));
    }

    public function testAViewWithALabelOfItsOwnKeepsIt(): void
    {
        $models = ReadModelLabels::distinct([self::modelOf('Till', self::readModel('Till', 'ConsultMyDay', 'My agenda'), self::readModel('Till', 'ConsultTimeline', 'Timeline'))]);

        self::assertSame(['My agenda', 'Timeline'], self::labelsOf($models));
    }

    public function testTheCopiesOfOneViewOnTwoWallsShareNoLabel(): void
    {
        $myDay = self::readModel('Till', 'ConsultMyDay', 'My agenda');

        self::assertSame(['My agenda', 'My agenda'], self::labelsOf(ReadModelLabels::distinct([self::modelOf('Till', $myDay), self::modelOf('Shop', $myDay)])));
    }

    public function testAViewKeepsALabelItSharesWithACommand(): void
    {
        $command = new Sticky('command:Book', 'command:Book', StickyType::Command, 'My agenda', null, 'Book', 'Book', 'Till', null);
        $models = ReadModelLabels::distinct([self::modelOf('Till', $command, self::readModel('Till', 'ConsultMyDay', 'My agenda'))]);

        self::assertSame(['My agenda', 'My agenda'], self::labelsOf($models));
    }

    public function testAPostItOfAnotherWallLeadsToAViewByItsNewLabel(): void
    {
        $port = new Sticky('externalSystem:Agenda', 'externalSystem:Agenda', StickyType::ExternalSystem, 'Agenda', null, 'Agenda', 'Agenda', 'Shop', null, answers: [new Elsewhere('Till', 'readModel:ConsultUpcomingExchanges', 'Timeline')]);
        $models = ReadModelLabels::distinct([self::modelOf('Till', self::readModel('Till', 'ConsultTimeline', 'Timeline'), self::readModel('Till', 'ConsultUpcomingExchanges', 'Timeline')), self::modelOf('Shop', $port)]);

        self::assertEquals([new Elsewhere('Till', 'readModel:ConsultUpcomingExchanges', 'Upcoming exchanges')], $models[1]->stickies[0]->answers);
    }

    public function testViewsOfTwoContextsMayShareALabel(): void
    {
        $models = ReadModelLabels::distinct([self::modelOf('Till', self::readModel('Till', 'CompareWithContact', 'Comparison')), self::modelOf('Shop', self::readModel('Shop', 'CompareWithOrganization', 'Comparison'))]);

        self::assertSame(['Comparison', 'Comparison'], self::labelsOf($models));
    }

    public function testViewsStillSharingALabelAfterTheirQueriesFailTheGenerationThatNamesThem(): void
    {
        $this->expectExceptionObject(SharedReadModelLabels::named(['Till: ConsultHistory, ConsultTimeline']));

        ReadModelLabels::distinct([self::modelOf('Till', self::readModel('Till', 'ConsultTimeline', 'Agenda'), self::readModel('Till', 'ConsultPlanning', 'Agenda'), self::readModel('Till', 'ConsultHistory', 'Timeline'))]);
    }

    private static function modelOf(string $context, Sticky ...$stickies): Model
    {
        return new Model($context, [], array_values($stickies), []);
    }

    private static function readModel(string $context, string $query, string $label): Sticky
    {
        $class = \sprintf('Prospecter\Core\%s\Application\%s\%s', $context, $query, $query);

        return new Sticky(StickyType::ReadModel->value.':'.$class, StickyType::ReadModel->value.':'.$query, StickyType::ReadModel, $label, null, $query, $class, $context, null);
    }

    /**
     * @param list<Model> $models
     *
     * @return list<string>
     */
    private static function labelsOf(array $models): array
    {
        return array_merge(...array_map(static fn (Model $model): array => array_map(static fn (Sticky $sticky): string => $sticky->label, $model->stickies), $models));
    }
}
