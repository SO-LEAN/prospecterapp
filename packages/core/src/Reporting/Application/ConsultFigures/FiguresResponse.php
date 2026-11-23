<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

/**
 * An account's figures: the organizations it prospects, the organizations placed on the map, its contacts, the
 * exchanges it keeps, and the exchanges in the requested month.
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
