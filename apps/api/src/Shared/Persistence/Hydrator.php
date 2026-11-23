<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\Typed;

/**
 * Reads and restores an aggregate's state without its constructor (ADR 0028, 0048). The domain keeps its named
 * constructors and private properties, and the persistence gives back what they built.
 */
final class Hydrator
{
    /**
     * @return array<string, mixed> every property of the aggregate, keyed by name
     */
    public static function stateOf(object $aggregate): array
    {
        return Typed::document(\Closure::bind(fn (): array => get_object_vars($this), $aggregate, $aggregate::class)());
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
