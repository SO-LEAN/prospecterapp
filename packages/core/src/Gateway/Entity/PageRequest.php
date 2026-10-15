<?php

declare(strict_types=1);

namespace Prospecter\Core\Gateway\Entity;

final readonly class PageRequest
{
    /**
     * @param array<string, mixed> $filter field => value the results must have
     */
    public function __construct(
        public int $page,
        public string $query,
        public int $maxByPage,
        public array $filter = [],
    ) {
    }
}
