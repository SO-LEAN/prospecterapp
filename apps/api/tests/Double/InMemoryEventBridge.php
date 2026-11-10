<?php

namespace Tests\App\Double;

use Aws\CommandInterface;
use Aws\Result;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * EventBridge as the client of the SDK reaches it, in memory: it keeps the entries of every PutEvents, and fails
 * some of the entries of the next one when a test asks it to, as the bus does when it throttles.
 */
final class InMemoryEventBridge
{
    /** @var list<list<array<string, string>>> the entries of each PutEvents, in order */
    public private(set) array $puts = [];
    private int $failingEntries = 0;

    public function failEntriesOfTheNextPut(int $count): void
    {
        $this->failingEntries = $count;
    }

    public function __invoke(CommandInterface $command): PromiseInterface
    {
        $entries = $command['Entries'];
        $failed = min($this->failingEntries, count($entries));
        $this->failingEntries = 0;
        $this->puts[] = $entries;

        return Create::promiseFor(new Result([
            'FailedEntryCount' => $failed,
            'Entries' => array_map(
                static fn (int $position): array => $position < $failed ? ['ErrorCode' => 'ThrottlingException', 'ErrorMessage' => 'Rate exceeded'] : ['EventId' => 'event-'.$position],
                array_keys($entries),
            ),
        ]));
    }
}
