<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\TakeOffTag;

final readonly class TakeOffTag
{
    /**
     * @param string $holder the kind of what is tagged: `organization` or `contact`
     */
    public function __construct(
        public string $memberId,
        public string $holder,
        public string $holderId,
        public string $tag,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
