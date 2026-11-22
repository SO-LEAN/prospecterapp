<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * A value a library, a document of DynamoDB or a JSON body gives untyped, taken as the type the API expects of it, any
 * other refused.
 */
final class Typed
{
    public static function string(mixed $value): string
    {
        return is_string($value) ? $value : throw self::unexpected('a string', $value);
    }

    public static function stringOrNull(mixed $value): ?string
    {
        return null === $value ? null : self::string($value);
    }

    public static function int(mixed $value): int
    {
        return is_int($value) ? $value : throw self::unexpected('an integer', $value);
    }

    public static function intOrNull(mixed $value): ?int
    {
        return null === $value ? null : self::int($value);
    }

    public static function float(mixed $value): float
    {
        return is_int($value) || is_float($value) ? (float) $value : throw self::unexpected('a number', $value);
    }

    public static function bool(mixed $value): bool
    {
        return is_bool($value) ? $value : throw self::unexpected('a boolean', $value);
    }

    /**
     * @return array<mixed>
     */
    public static function array(mixed $value): array
    {
        return is_array($value) ? $value : throw self::unexpected('an array', $value);
    }

    /**
     * @return list<string>
     */
    public static function strings(mixed $value): array
    {
        return array_values(array_map(self::string(...), self::array($value)));
    }

    /**
     * A JSON object or a DynamoDB map, its fields by name; an empty one is an empty array.
     *
     * @return array<string, mixed>
     */
    public static function document(mixed $value): array
    {
        $document = [];

        foreach (self::array($value) as $name => $field) {
            $document[(string) $name] = $field;
        }

        return $document;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function documents(mixed $value): array
    {
        return array_values(array_map(self::document(...), self::array($value)));
    }

    /**
     * @return array<string, string>
     */
    public static function namedStrings(mixed $value): array
    {
        return array_map(self::string(...), self::document($value));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function object(mixed $value, string $class): object
    {
        return $value instanceof $class ? $value : throw self::unexpected($class, $value);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public static function objectOrNull(mixed $value, string $class): ?object
    {
        return null === $value ? null : self::object($value, $class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    public static function objects(mixed $value, string $class): array
    {
        return array_values(array_map(static fn (mixed $element): object => self::object($element, $class), self::array($value)));
    }

    private static function unexpected(string $expected, mixed $value): \UnexpectedValueException
    {
        return new \UnexpectedValueException(sprintf('Expected %s, got %s.', $expected, get_debug_type($value)));
    }
}
