<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\SetDailyCap;

final readonly class SetDailyCap
{
    /**
     * @param int|null $dailyCap the most recipients processed a day, a guard only; none to take the cap off
     */
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public ?int $dailyCap,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
