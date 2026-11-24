<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

/**
 * The command a reaction to an account's opening sends (FailingReaction).
 */
final readonly class ReactToTheOpening
{
    public function __construct(
        public string $accountId,
        public string $founderId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
