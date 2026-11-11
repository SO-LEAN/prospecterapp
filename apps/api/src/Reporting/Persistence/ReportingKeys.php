<?php

namespace App\Reporting\Persistence;

use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * The keys of the items of the table of Reporting, written by the repository and read by the retriever (ADR 0088):
 * the versions under `PROJECTION`, the facts of an account in a version under `FACTS#<version>#<account>`, its figures
 * under `FIGURES#<version>#<account>`; the names of the figures the repository adds to and the retriever reads.
 */
final class ReportingKeys
{
    public const string PROJECTION = 'PROJECTION';
    public const string ACTIVE = 'ACTIVE';
    public const string BUILDING = 'BUILDING';
    public const string TOTAL = 'TOTAL';
    /** the number of exchanges, in the figures of an account and in those of a month */
    public const string EXCHANGES = 'exchanges';
    private const string FACTS = 'FACTS#';
    private const string FIGURES = 'FIGURES#';
    private const string ORGANIZATION = 'ORGANIZATION#';
    private const string EXCHANGE = 'EXCHANGE#';
    private const string CONTACT = 'CONTACT#';
    private const string MONTH = 'MONTH#';

    public static function facts(int $version, AccountId $accountId): string
    {
        return self::ofTheVersion($version, self::FACTS).$accountId->value;
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

    public static function figures(int $version, AccountId $accountId): string
    {
        return self::ofTheVersion($version, self::FIGURES).$accountId->value;
    }

    public static function month(Month $month): string
    {
        return self::MONTH.$month->value;
    }

    /**
     * @return list<string> the beginnings of the partitions of a version, its facts and its figures
     */
    public static function partitionsOf(int $version): array
    {
        return [self::ofTheVersion($version, self::FACTS), self::ofTheVersion($version, self::FIGURES)];
    }

    /**
     * @return list<string> the beginnings of the partitions of every version
     */
    public static function partitionsOfEveryVersion(): array
    {
        return [self::FACTS, self::FIGURES];
    }

    /**
     * The version a partition of facts or of figures belongs to: `FACTS#<version>#<account>`.
     */
    public static function versionOf(string $partition): int
    {
        return (int) explode('#', $partition)[1];
    }

    /**
     * The separator after the version, so that the version 1 is not the beginning of the version 10.
     */
    private static function ofTheVersion(int $version, string $kind): string
    {
        return $kind.$version.'#';
    }
}
