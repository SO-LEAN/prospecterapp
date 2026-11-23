<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

/**
 * How much each of an account's figures changes. The change is added to the current figures, so two changes written
 * at the same time both count.
 */
final readonly class FiguresChange
{
    /**
     * @param array<string, int> $exchangesByMonth each month's change in exchanges, keyed by the month (YYYY-MM)
     */
    public function __construct(
        public int $activeOrganizations = 0,
        public int $locatedOrganizations = 0,
        public int $contacts = 0,
        public int $exchanges = 0,
        public array $exchangesByMonth = [],
    ) {
    }

    public static function none(): self
    {
        return new self();
    }

    #[\NoDiscard]
    public function minus(self $other): self
    {
        return new self(
            $this->activeOrganizations - $other->activeOrganizations,
            $this->locatedOrganizations - $other->locatedOrganizations,
            $this->contacts - $other->contacts,
            $this->exchanges - $other->exchanges,
            self::monthsMinus($this->exchangesByMonth, $other->exchangesByMonth),
        );
    }

    /**
     * @param array<string, int> $months
     * @param array<string, int> $others
     *
     * @return array<string, int> the months whose figure changes
     */
    private static function monthsMinus(array $months, array $others): array
    {
        $differences = [];

        foreach (array_keys($months + $others) as $month) {
            $differences[$month] = ($months[$month] ?? 0) - ($others[$month] ?? 0);
        }

        return array_filter($differences);
    }
}
