<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Event\RecordsEvents;

/**
 * Reporting only reads: it keeps each account's figures from the facts the other contexts publish, and records no event
 * of its own (ADR 0103). No class of Reporting is an event or an aggregate that records one. So its wall draws each of
 * its commands as a projection.
 */
class ReportingRecordsNoEventTest extends TestCase
{
    /**
     * @param class-string $class
     */
    #[DataProvider('reportingClasses')]
    public function testAClassOfReportingIsNoEvent(string $class): void
    {
        $this->assertFalse(is_a($class, DomainEvent::class, true), $class.' is an event, but Reporting records no event');
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('reportingClasses')]
    public function testAClassOfReportingIsNoAggregateRecordingEvents(string $class): void
    {
        $this->assertNotContains(RecordsEvents::class, self::traitsOf($class), $class.' records events, but Reporting records no event');
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    private static function traitsOf(string $class): array
    {
        $traits = [];
        for ($reflection = new \ReflectionClass($class); false !== $reflection; $reflection = $reflection->getParentClass()) {
            $traits = [...$traits, ...$reflection->getTraitNames()];
        }

        return $traits;
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function reportingClasses(): iterable
    {
        $root = dirname(__DIR__, 2).'/src/';

        foreach (FilesUnder::the($root.'Reporting') as $path) {
            $class = 'Prospecter\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($root)));

            if (class_exists($class)) {
                yield $class => [$class];
            }
        }
    }
}
