<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeId;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\Reporting\Domain\Policy\FactSuccession;
use Prospecter\Core\Reporting\Domain\Policy\FiguresDelta;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * What a fact received changes in the figures of its account, when it takes the place of the known one.
 */
class FiguresDeltaTest extends TestCase
{
    private const string ACCOUNT = '0199a000-0000-7000-8000-000000000001';
    private const string ORGANIZATION = '0199a000-0000-7000-8000-000000000002';
    private const string EXCHANGE = '0199a000-0000-7000-8000-000000000003';

    /**
     * @param array{int, int} $expected the change of the active, then of the located organizations
     */
    #[DataProvider('organizations')]
    public function testAnOrganizationFact(?OrganizationFact $known, OrganizationFact $received, bool $replaces, array $expected): void
    {
        $this->assertSame($replaces, FactSuccession::replaces($received, $known));
        $change = FiguresDelta::between($known, $received);
        $this->assertSame($expected, [$change->activeOrganizations, $change->locatedOrganizations]);
    }

    /**
     * @return iterable<string, array{?OrganizationFact, OrganizationFact, bool, array{int, int}}>
     */
    public static function organizations(): iterable
    {
        yield 'none known, present and located' => [null, self::organization(1, true, true), true, [1, 1]];
        yield 'present, then located' => [self::organization(1, true, false), self::organization(2, true, true), true, [0, 1]];
        yield 'located, then terminal' => [self::organization(5, true, true), self::organization(0, false, false), true, [-1, -1]];
        yield 'none known, terminal' => [null, self::organization(0, false, false), true, [0, 0]];
        yield 'terminal, then present' => [self::organization(0, false, false), self::organization(1, true, true), false, [1, 1]];
        yield 'terminal twice' => [self::organization(0, false, false), self::organization(0, false, false), false, [0, 0]];
        yield 'an older revision' => [self::organization(5, true, true), self::organization(4, true, false), false, [0, -1]];
        yield 'the same revision' => [self::organization(5, true, true), self::organization(5, true, false), false, [0, -1]];
    }

    public function testAnExchangeMovedToAnotherMonthLeavesTheFormerOne(): void
    {
        $change = FiguresDelta::between(self::exchange(1, '2026-10-15 14:30'), self::exchange(2, '2026-09-30 09:00'));

        $this->assertEquals(new FiguresChange(exchanges: 0, exchangesByMonth: ['2026-09' => 1, '2026-10' => -1]), $change);
    }

    public function testAnExchangeKeptInItsMonthChangesNoFigure(): void
    {
        $this->assertEquals(FiguresChange::none(), FiguresDelta::between(self::exchange(1, '2026-10-15 14:30'), self::exchange(2, '2026-10-16 09:00')));
    }

    public function testAnErasedExchangeLeavesItsMonth(): void
    {
        $erased = new ExchangeFact(new AccountId(self::ACCOUNT), new ExchangeId(self::EXCHANGE), 0, false, null);

        $this->assertEquals(new FiguresChange(exchanges: -1, exchangesByMonth: ['2026-10' => -1]), FiguresDelta::between(self::exchange(1, '2026-10-15 14:30'), $erased));
    }

    private static function organization(int $revision, bool $present, bool $located): OrganizationFact
    {
        return new OrganizationFact(new AccountId(self::ACCOUNT), new OrganizationId(self::ORGANIZATION), $revision, $present, $located);
    }

    private static function exchange(int $revision, string $notedAt): ExchangeFact
    {
        return new ExchangeFact(new AccountId(self::ACCOUNT), new ExchangeId(self::EXCHANGE), $revision, true, new \DateTimeImmutable($notedAt, new \DateTimeZone('UTC')));
    }
}
