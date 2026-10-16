<?php

namespace App\Shared\Persistence;

/**
 * Reads and restores the state of an aggregate without its constructor (ADR 0028, 0048): the domain keeps
 * its named constructors and private properties, the persistence gives back what they built.
 */
final class Hydrator
{
    /**
     * @return array<string, mixed> every property of the aggregate, by name
     */
    public static function stateOf(object $aggregate): array
    {
        return \Closure::bind(fn (): array => get_object_vars($this), $aggregate, $aggregate::class)();
    }

    /**
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $properties
     *
     * @return T
     */
    public static function restore(string $class, array $properties): object
    {
        $aggregate = new \ReflectionClass($class)->newInstanceWithoutConstructor();
        \Closure::bind(function (array $properties): void {
            foreach ($properties as $name => $value) {
                $this->{$name} = $value;
            }
        }, $aggregate, $class)($properties);

        return $aggregate;
    }
}
