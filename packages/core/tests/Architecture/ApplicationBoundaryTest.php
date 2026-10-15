<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;

/**
 * The domain does not cross the application (ADR 0035): what a client sends (requests) and receives
 * (responses, refusals) is plain data, never a class of a Domain namespace.
 */
class ApplicationBoundaryTest extends TestCase
{
    private const string NAMESPACE = 'Prospecter\Core\\';

    #[DataProvider('handlersCalledByAClient')]
    public function testAHandlerTakesAndAnswersNoDomainClass(string $handler): void
    {
        $invoke = new \ReflectionMethod($handler, '__invoke');

        $this->assertSame([], $this->domainClassesIn([$invoke->getParameters()[0]->getType(), $invoke->getReturnType()]));
    }

    #[DataProvider('handlers')]
    public function testAHandlerIsCalledThroughTheRefusalTranslation(string $handler): void
    {
        $this->assertTrue(
            is_a($handler, CommandHandler::class, true) || is_a($handler, QueryHandler::class, true) || is_a($handler, EventHandler::class, true),
            'A handler implements CommandHandler or QueryHandler, so that RefusalTranslatingHandler decorates it, or EventHandler, called within a command decorated so',
        );
    }

    #[DataProvider('dataStructures')]
    public function testARequestOrAResponseCarriesNoDomainClass(string $dataStructure): void
    {
        $properties = new \ReflectionClass($dataStructure)->getProperties(\ReflectionProperty::IS_PUBLIC);

        $this->assertSame([], $this->domainClassesIn(array_map(static fn (\ReflectionProperty $property): ?\ReflectionType => $property->getType(), $properties)));
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function handlers(): iterable
    {
        foreach (self::applicationClasses() as $class) {
            if (str_ends_with($class, 'Handler') && RefusalTranslatingHandler::class !== $class && !interface_exists($class)) {
                yield $class => [$class];
            }
        }
    }

    /**
     * The command and query handlers: an event handler reacts to a domain event within the core, no client
     * calls it.
     *
     * @return iterable<string, array{class-string}>
     */
    public static function handlersCalledByAClient(): iterable
    {
        foreach (self::handlers() as $name => [$handler]) {
            if (!is_a($handler, EventHandler::class, true)) {
                yield $name => [$handler];
            }
        }
    }

    /**
     * Every class of an application layer exposing public properties: the requests, the responses and what
     * they carry.
     *
     * @return iterable<string, array{class-string}>
     */
    public static function dataStructures(): iterable
    {
        foreach (self::applicationClasses() as $class) {
            if ([] !== new \ReflectionClass($class)->getProperties(\ReflectionProperty::IS_PUBLIC)) {
                yield $class => [$class];
            }
        }
    }

    /**
     * @return list<class-string>
     */
    private static function applicationClasses(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $classes = [];

        foreach (glob($root.'*/Application', GLOB_ONLYDIR) ?: [] as $application) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($application, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $class = self::NAMESPACE.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($root)));

                if (class_exists($class)) {
                    $classes[] = $class;
                }
            }
        }

        sort($classes);

        return $classes;
    }

    /**
     * @param list<\ReflectionType|null> $types
     *
     * @return list<string>
     */
    private function domainClassesIn(array $types): array
    {
        $names = [];

        foreach ($types as $type) {
            $names = [...$names, ...$this->namesOf($type)];
        }

        return array_values(array_filter($names, static fn (string $name): bool => str_contains($name, '\Domain\\')));
    }

    /**
     * @return list<string>
     */
    private function namesOf(?\ReflectionType $type): array
    {
        return match (true) {
            $type instanceof \ReflectionNamedType => [$type->getName()],
            $type instanceof \ReflectionUnionType, $type instanceof \ReflectionIntersectionType => array_merge(...array_map($this->namesOf(...), $type->getTypes())),
            default => [],
        };
    }
}
