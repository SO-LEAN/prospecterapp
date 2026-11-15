<?php

namespace App\Connectors\AntiCorruption;

use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeStatus;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesService;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\KeptHeldExchanges;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Anticorruption layer (ADR 0045, 0089): Connectors reads an exchange as it is now from Prospecting, asked about a set
 * of one (ADR 0127).
 */
final readonly class ProspectingHeldExchanges implements HeldExchanges
{
    public function __construct(private ConsultHeldExchangesService $consultHeldExchangesService)
    {
    }

    public function find(AccountId $accountId, ExchangeId $id): ?HeldExchange
    {
        $kept = new KeptHeldExchanges();
        ($this->consultHeldExchangesService)(new ConsultHeldExchanges($accountId->value, [$id->value]), $kept);
        $held = $kept->exchanges[0] ?? null;

        return null === $held ? null : new HeldExchange(
            $id,
            $accountId,
            new MemberId($held->holderId),
            ExchangeStatus::from($held->status),
            Channel::tryFrom($held->channel),
            $held->startsAt,
            $held->durationInMinutes,
            $held->place ?? $held->link,
            null === $held->contactId ? null : new PersonId($held->contactId),
            new OrganizationId($held->organizationId),
            $held->organizationName,
        );
    }
}
