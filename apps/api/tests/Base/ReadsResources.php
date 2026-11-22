<?php

declare(strict_types=1);

namespace Tests\App\Base;

/**
 * Reads a field of a resource the API answered, by its path, typed: a test fails at once on a field missing or of
 * another type, and PHPStan knows what it reads.
 */
trait ReadsResources
{
    /**
     * @param array<array-key, mixed> $resource
     */
    protected static function valueAt(array $resource, string|int ...$path): mixed
    {
        $value = $resource;

        foreach ($path as $key) {
            \assert(is_array($value) && array_key_exists($key, $value), sprintf('The resource has no %s', implode('.', $path)));
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * A JSON object as a record, its keys strings.
     *
     * @return array<string, mixed>
     */
    protected static function record(mixed $value): array
    {
        \assert(is_array($value), 'An object was expected');
        $record = [];

        foreach ($value as $key => $field) {
            $record[(string) $key] = $field;
        }

        return $record;
    }

    /**
     * A JSON list of objects as records.
     *
     * @return list<array<string, mixed>>
     */
    protected static function records(mixed $value): array
    {
        \assert(is_array($value), 'A list was expected');

        return array_values(array_map(self::record(...), $value));
    }

    /**
     * @param array<array-key, mixed> $resource
     */
    protected static function stringAt(array $resource, string|int ...$path): string
    {
        $value = self::valueAt($resource, ...$path);
        \assert(is_string($value), sprintf('%s is no string', implode('.', $path)));

        return $value;
    }

    /**
     * @param array<array-key, mixed> $resource
     */
    protected static function intAt(array $resource, string|int ...$path): int
    {
        $value = self::valueAt($resource, ...$path);
        \assert(is_int($value), sprintf('%s is no integer', implode('.', $path)));

        return $value;
    }
}
