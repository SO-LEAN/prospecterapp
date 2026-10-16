<?php

namespace App\Shared\Persistence;

use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * A transaction was cancelled: the condition of some of its writes did not hold, nothing was written.
 */
final class ConditionFailed extends \RuntimeException
{
    /**
     * @param list<int> $failedWrites the positions of the writes whose condition did not hold
     */
    private function __construct(public readonly array $failedWrites, DynamoDbException $previous)
    {
        parent::__construct('A condition of the transaction did not hold', previous: $previous);
    }

    public static function in(DynamoDbException $cancelled): self
    {
        $reasons = $cancelled->get('CancellationReasons') ?? [];

        return new self(array_keys(array_filter($reasons, static fn (array $reason): bool => 'ConditionalCheckFailed' === ($reason['Code'] ?? null))), $cancelled);
    }

    public function failed(int $write): bool
    {
        return in_array($write, $this->failedWrites, true);
    }
}
