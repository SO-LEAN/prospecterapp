<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

/**
 * Asks for the member's account figures, with the exchanges in the given moment's month.
 */
final readonly class ConsultFigures
{
    public function __construct(
        public string $memberId,
        public \DateTimeImmutable $at,
    ) {
    }
}
