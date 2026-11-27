<?php

declare(strict_types=1);

namespace App\Booking\AntiCorruption;

use Prospecter\Core\Booking\Domain\Model\PlannedExchange;
use Prospecter\Core\Booking\Domain\Port\HostAgendas;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchangesService;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\HeldExchangeResponse;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\KeptHeldExchanges;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Anticorruption layer (ADR 0045, 0089, 0153). Booking learns from Prospecting the exchanges a host still has planned,
 * with their duration when they have one.
 */
final readonly class ProspectingHostAgendas implements HostAgendas
{
    public function __construct(private ConsultPlannedExchangesService $consultPlannedExchangesService)
    {
    }

    public function plannedExchangesOf(AccountId $accountId, MemberId $host, \DateTimeImmutable $from): array
    {
        $kept = new KeptHeldExchanges();
        ($this->consultPlannedExchangesService)(new ConsultPlannedExchanges($accountId->value, $host->value, $from), $kept);

        return array_map(static fn (HeldExchangeResponse $exchange): PlannedExchange => new PlannedExchange($exchange->startsAt, $exchange->durationInMinutes), $kept->exchanges);
    }
}
