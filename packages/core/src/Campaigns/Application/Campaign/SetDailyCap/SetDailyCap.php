<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Campaign\SetDailyCap;

final readonly class SetDailyCap
{
    /**
     * @param int|null $dailyCap the most recipients processed in a day, only a guard; null takes the cap off
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public ?int $dailyCap,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
