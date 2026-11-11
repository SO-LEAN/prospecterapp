<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

/**
 * The figures of an account: the organizations it prospects, those placed on the map, its contacts, the exchanges it
 * keeps and those of the month.
 */
final readonly class FiguresResponse
{
    public function __construct(
        public int $activeOrganizations,
        public int $locatedOrganizations,
        public int $contacts,
        public int $exchanges,
        public int $exchangesThisMonth,
    ) {
    }

    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0);
    }
}
