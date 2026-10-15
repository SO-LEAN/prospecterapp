<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

/**
 * Assertions of the scenarios. PHPUnit's need its runner to describe a failure, Behat is not that runner.
 */
final class Expect
{
    public static function that(bool $condition, string $failure): void
    {
        if (!$condition) {
            throw new \RuntimeException($failure);
        }
    }

    public static function same(mixed $expected, mixed $actual): void
    {
        self::that($expected === $actual, sprintf("Expected:\n%s\nActual:\n%s", self::export($expected), self::export($actual)));
    }

    /**
     * The labels of the scenario table are described this way, whatever their order.
     *
     * @param array<string, string> $expected labels the scenario cares about
     * @param array<string, string> $actual   everything that can be said
     */
    public static function describes(array $expected, array $actual): void
    {
        $unknown = array_keys(array_diff_key($expected, $actual));
        self::that([] === $unknown, 'Unknown labels: '.implode(', ', $unknown));

        $described = array_intersect_key($actual, $expected);
        ksort($expected);
        ksort($described);
        self::same($expected, $described);
    }

    private static function export(mixed $value): string
    {
        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
