<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\OpenAccount;

final readonly class OpenAccount
{
    public function __construct(
        public string $id,
        public ?string $corporateName,
        public ?string $form,
        public string $founderId,
        public string $founderProfileId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
