<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ReviseLegalIdentity;

final readonly class ReviseLegalIdentity
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
