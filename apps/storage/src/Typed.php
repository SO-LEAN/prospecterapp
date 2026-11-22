<?php

declare(strict_types=1);

namespace App;

/**
 * A value the AWS SDK gives untyped, taken as the type the storage expects of it, any other refused.
 */
final class Typed
{
    public static function string(mixed $value): string
    {
        return is_string($value) || $value instanceof \Stringable ? (string) $value : throw self::unexpected('a string', $value);
    }

    public static function int(mixed $value): int
    {
        return is_int($value) ? $value : throw self::unexpected('an integer', $value);
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
     * @return array<string, string>
     */
    public static function namedStrings(mixed $value): array
    {
        $named = [];

        foreach (self::array($value) as $name => $string) {
            $named[(string) $name] = self::string($string);
        }

        return $named;
    }

    private static function unexpected(string $expected, mixed $value): \UnexpectedValueException
    {
        return new \UnexpectedValueException(sprintf('Expected %s, got %s.', $expected, get_debug_type($value)));
    }
}
