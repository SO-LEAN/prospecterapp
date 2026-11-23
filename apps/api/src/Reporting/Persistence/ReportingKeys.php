<?php

declare(strict_types=1);

namespace App\Reporting\Persistence;

use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;

/**
 * The keys of the items in Reporting's table. The repository writes them and the retriever reads them (ADR 0088). The
 * versions are under `PROJECTION`. An account's facts in a version are under `FACTS#<version>#<account>`, and its
 * figures under `FIGURES#<version>#<account>`. The class also names the figures that the repository adds to and the
 * retriever reads.
 */
final class ReportingKeys
{
    public const string PROJECTION = 'PROJECTION';
    public const string ACTIVE = 'ACTIVE';
    public const string BUILDING = 'BUILDING';
    public const string TOTAL = 'TOTAL';
    /** the number of exchanges, in an account's total figures and in a month's figures */
    public const string EXCHANGES = 'exchanges';
    /** the sort key prefix for a month's figures */
    public const string MONTH = 'MONTH#';
    private const string FACTS = 'FACTS#';
    private const string FIGURES = 'FIGURES#';
    private const string ORGANIZATION = 'ORGANIZATION#';
    private const string EXCHANGE = 'EXCHANGE#';
    private const string CONTACT = 'CONTACT#';

    public static function facts(int $version, string $accountId): string
    {
        return self::ofTheVersion($version, self::FACTS).$accountId;
    }

    public static function fact(Fact $fact): string
    {
        return match (true) {
            $fact instanceof OrganizationFact => self::ORGANIZATION,
            $fact instanceof ExchangeFact => self::EXCHANGE,
            $fact instanceof ContactFact => self::CONTACT,
            default => throw new \LogicException(sprintf('No item keeps a %s', $fact::class)),
        }.$fact->id->value;
    }

    public static function figures(int $version, string $accountId): string
    {
        return self::ofTheVersion($version, self::FIGURES).$accountId;
    }

    public static function month(Month $month): string
    {
        return self::MONTH.$month->value;
    }

    /**
     * @return list<string> the prefixes of a version's partitions, for its facts and its figures
     */
    public static function partitionsOf(int $version): array
    {
        return [self::ofTheVersion($version, self::FACTS), self::ofTheVersion($version, self::FIGURES)];
    }

    /**
     * @return list<string> the partition prefixes of every version
     */
    public static function partitionsOfEveryVersion(): array
    {
        return [self::FACTS, self::FIGURES];
    }

    /**
     * Returns the version that a fact or figure partition belongs to: `FACTS#<version>#<account>`.
     */
    public static function versionOf(string $partition): int
    {
        return (int) explode('#', $partition)[1];
    }

    /**
     * Returns the version's partition prefix. The separator after the version keeps version 1 from being a prefix of
     * version 10.
     */
    private static function ofTheVersion(int $version, string $kind): string
    {
        return $kind.$version.'#';
    }
}
