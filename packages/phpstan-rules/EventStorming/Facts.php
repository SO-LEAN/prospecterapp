<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * Everything the analysis read in the code: its classes, their calls, and the arguments of the objects they create.
 */
final readonly class Facts
{
    /** @var array<string, ClassFacts> */
    private array $classes;

    /**
     * @param list<ClassFacts> $classes
     * @param list<Call>       $calls
     * @param list<Argument>   $arguments
     */
    public function __construct(array $classes, public array $calls, public array $arguments = [])
    {
        $this->classes = array_column($classes, null, 'name');
    }

    /**
     * @param array<mixed> $collected the metadata of EventStormingFactsRule
     */
    public static function read(array $collected): self
    {
        return new self(
            array_map(
                static fn (array $class): ClassFacts => new ClassFacts(
                    self::text($class['name'] ?? null),
                    self::texts($class['interfaces'] ?? null),
                    array_values(array_map(self::texts(...), \is_array($class['parameters'] ?? null) ? $class['parameters'] : [])),
                    self::texts($class['returns'] ?? null),
                    true === ($class['mayReturnNothing'] ?? null),
                    self::texts($class['dependencies'] ?? null),
                    self::texts($class['roles'] ?? null),
                    self::texts($class['presents'] ?? null),
                    self::texts($class['routes'] ?? null),
                    self::textsByName($class['optionalParameters'] ?? null),
                    self::texts($class['outputPorts'] ?? null),
                ),
                self::records($collected['classes'] ?? null),
            ),
            array_map(
                static fn (array $call): Call => new Call(self::text($call['caller'] ?? null), self::text($call['callerMethod'] ?? null), self::text($call['target'] ?? null), self::text($call['targetMethod'] ?? null), true === ($call['conditional'] ?? null)),
                self::records($collected['calls'] ?? null),
            ),
            array_map(
                static fn (array $argument): Argument => new Argument(self::text($argument['caller'] ?? null), self::text($argument['created'] ?? null), self::text($argument['parameter'] ?? null), self::texts($argument['texts'] ?? null)),
                self::records($collected['arguments'] ?? null),
            ),
        );
    }

    /**
     * @return list<array<mixed>>
     */
    private static function records(mixed $records): array
    {
        return \is_array($records) ? array_values(array_filter($records, \is_array(...))) : [];
    }

    /**
     * @return list<string>
     */
    private static function texts(mixed $texts): array
    {
        return \is_array($texts) ? array_values(array_filter($texts, \is_string(...))) : [];
    }

    /**
     * @return array<string, string>
     */
    private static function textsByName(mixed $texts): array
    {
        return \is_array($texts) ? array_filter($texts, static fn (mixed $text, int|string $name): bool => \is_string($text) && \is_string($name), \ARRAY_FILTER_USE_BOTH) : [];
    }

    private static function text(mixed $text): string
    {
        return \is_string($text) ? $text : '';
    }

    /**
     * @return list<ClassFacts>
     */
    public function classes(): array
    {
        return array_values($this->classes);
    }

    /**
     * @return list<Call>
     */
    public function callsFrom(string $class, string $method): array
    {
        return array_values(array_filter($this->calls, static fn (Call $call): bool => $call->caller === $class && $call->callerMethod === $method));
    }

    public function find(string $class): ?ClassFacts
    {
        return $this->classes[$class] ?? null;
    }
}
