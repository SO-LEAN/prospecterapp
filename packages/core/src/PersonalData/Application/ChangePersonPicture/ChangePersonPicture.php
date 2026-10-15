<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonPicture;

final readonly class ChangePersonPicture
{
    public function __construct(
        public string $readerId,
        public string $id,
        public \SplFileInfo $picture,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
