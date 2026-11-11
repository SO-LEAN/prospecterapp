<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

/**
 * The figures of the account of the member, its exchanges of the month of the moment given.
 */
final readonly class ConsultFigures
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $at,
    ) {
    }
}
