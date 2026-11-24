<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * Everything the analysis read of the code: its classes and their calls.
 */
final readonly class Facts
{
    /** @var array<string, ClassFacts> */
    private array $classes;

    /**
     * @param list<ClassFacts> $classes
     * @param list<Call>       $calls
     */
    public function __construct(array $classes, public array $calls)
    {
        $this->classes = array_column($classes, null, 'name');
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
