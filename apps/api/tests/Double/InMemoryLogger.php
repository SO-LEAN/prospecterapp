<?php

namespace Tests\App\Double;

use Psr\Log\AbstractLogger;

/**
 * A logger keeping what it is given, for a test to read what a service logged.
 */
final class InMemoryLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> in order */
    public private(set) array $records = [];

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }
}
