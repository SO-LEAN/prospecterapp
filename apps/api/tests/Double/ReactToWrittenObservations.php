<?php

declare(strict_types=1);

namespace Tests\App\Double;

/**
 * The command of the tests' reaction to written observations (FailingReactionPolicy, FailingReaction).
 */
final readonly class ReactToWrittenObservations
{
    public function __construct(public string $organizationId, public \DateTimeImmutable $executedAt)
    {
    }
}
