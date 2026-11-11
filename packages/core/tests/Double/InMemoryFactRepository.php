<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository;

/**
 * The facts of each version over the InMemoryDatabase, and the figures they make, each a counter a change is added
 * to, as DynamoDB adds to a number. Within a fiber, a reading yields once it has read: the scenarios interleave two
 * recordings as two invocations of the worker may.
 */
final readonly class InMemoryFactRepository implements FactRepository
{
    public const string ACTIVE_ORGANIZATIONS = 'activeOrganizations';
    public const string LOCATED_ORGANIZATIONS = 'locatedOrganizations';
    public const string CONTACTS = 'contacts';
    public const string EXCHANGES = 'exchanges';
    private const string EXCHANGES_OF_THE_MONTH = 'exchanges#';

    public function __construct(private InMemoryDatabase $database)
    {
    }

    public static function exchangesOf(string $month): string
    {
        return self::EXCHANGES_OF_THE_MONTH.$month;
    }

    public function find(int $version, Fact $about): ?Fact
    {
        $known = $this->database->fact($version, self::aggregateOf($about));

        if (null !== \Fiber::getCurrent()) {
            \Fiber::suspend();
        }

        return $known;
    }

    public function record(int $version, Fact $fact, ?Fact $replaced, FiguresChange $change): void
    {
        $this->database->storeFact($version, self::aggregateOf($fact), $fact);

        foreach ($this->figuresChangedBy($change) as $name => $difference) {
            $this->database->addToFigure($version, $fact->accountId->value, $name, $difference);
        }
    }

    private static function aggregateOf(Fact $fact): string
    {
        return $fact::class.'#'.$fact->id->value;
    }

    /**
     * @return array<string, int>
     */
    private function figuresChangedBy(FiguresChange $change): array
    {
        $months = [];

        foreach ($change->exchangesByMonth as $month => $difference) {
            $months[self::exchangesOf($month)] = $difference;
        }

        return [
            self::ACTIVE_ORGANIZATIONS => $change->activeOrganizations,
            self::LOCATED_ORGANIZATIONS => $change->locatedOrganizations,
            self::CONTACTS => $change->contacts,
            self::EXCHANGES => $change->exchanges,
            ...$months,
        ];
    }
}
