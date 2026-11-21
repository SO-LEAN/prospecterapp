<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Policy\FactSuccession;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Which fact of an aggregate Campaigns keeps, the facts arriving in any order and maybe more than once (ADR 0141).
 */
final class CampaignsFactSuccessionTest extends TestCase
{
    private const string ACCOUNT = '019a0000-0000-7000-8000-0000000000a1';
    private const string ACME = '019a0000-0000-7000-8000-000000000001';
    private const int TERMINAL = 0;

    #[DataProvider('successions')]
    public function testAFactReceivedReplacesTheKnownOneOnlyWhenItIsLater(?OrganizationFact $known, OrganizationFact $received, bool $replaces): void
    {
        $this->assertSame($replaces, FactSuccession::replaces($received, $known));
    }

    /**
     * @return iterable<string, array{?OrganizationFact, OrganizationFact, bool}>
     */
    public static function successions(): iterable
    {
        yield 'none known' => [null, self::present(1), true];
        yield 'a higher revision' => [self::present(2), self::present(3), true];
        yield 'the same revision, received twice' => [self::present(2), self::present(2), false];
        yield 'a lower revision, arriving late' => [self::present(3), self::present(2), false];
        yield 'the terminal fact after a present one' => [self::present(5), self::terminal(), true];
        yield 'a present fact after the terminal one' => [self::terminal(), self::present(6), false];
        yield 'the terminal fact received twice' => [self::terminal(), self::terminal(), false];
    }

    #[DataProvider('rebuilds')]
    public function testAFactRebuiltReplacesTheKnownOneAtTheSameRevisionToo(?OrganizationFact $known, OrganizationFact $rebuilt, bool $replaces): void
    {
        $this->assertSame($replaces, FactSuccession::replacesAsRebuilt($rebuilt, $known));
    }

    /**
     * @return iterable<string, array{?OrganizationFact, OrganizationFact, bool}>
     */
    public static function rebuilds(): iterable
    {
        yield 'the same revision, enriched since' => [self::present(2), self::present(2), true];
        yield 'a lower revision, a later one received meanwhile' => [self::present(3), self::present(2), false];
        yield 'the terminal fact rebuilt after a present one' => [self::present(5), self::terminal(), true];
        yield 'a present fact rebuilt after the terminal one' => [self::terminal(), self::present(6), false];
    }

    private static function present(int $revision): OrganizationFact
    {
        return new OrganizationFact(new AccountId(self::ACCOUNT), new OrganizationId(self::ACME), $revision, true, [], Locality::unknown());
    }

    private static function terminal(): OrganizationFact
    {
        return new OrganizationFact(new AccountId(self::ACCOUNT), new OrganizationId(self::ACME), self::TERMINAL, false, [], Locality::unknown());
    }
}
