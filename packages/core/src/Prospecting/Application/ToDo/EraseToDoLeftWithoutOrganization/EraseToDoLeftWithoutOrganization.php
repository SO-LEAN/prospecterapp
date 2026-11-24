<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ToDo\EraseToDoLeftWithoutOrganization;

final readonly class EraseToDoLeftWithoutOrganization
{
    public function __construct(
        public string $memberId,
        public string $toDoId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
