<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Domain\Model\BusyPeriod;
use Prospecter\Core\Booking\Domain\Port\HostAgendas;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchangesService;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\HeldExchangeResponse;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\KeptHeldExchanges;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089, 0153): Booking knows when a host is taken from
 * the exchanges they still have planned in Prospecting, an exchange planned without a duration taking one slot.
 */
final readonly class ProspectingHostAgendas implements HostAgendas
{
    private const int MINUTES_OF_AN_EXCHANGE_WITHOUT_DURATION = 15;

    public function __construct(private ConsultPlannedExchangesService $consultPlannedExchangesService)
    {
    }

    public function busyPeriodsOf(AccountId $accountId, MemberId $host, \DateTimeImmutable $from): array
    {
        $kept = new KeptHeldExchanges();
        ($this->consultPlannedExchangesService)(new ConsultPlannedExchanges($accountId->value, $host->value, $from), $kept);

        return array_map(static fn (HeldExchangeResponse $exchange): BusyPeriod => new BusyPeriod($exchange->startsAt, $exchange->durationInMinutes ?? self::MINUTES_OF_AN_EXCHANGE_WITHOUT_DURATION), $kept->exchanges);
    }
}
