<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeStatus;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Port\HeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesService;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultPlannedExchangesService;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\HeldExchangeResponse;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\KeptHeldExchanges;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089). Connectors reads an exchange as it is now from
 * Prospecting, asking about a set of one (ADR 0127). It also reads the exchanges that a holder planned from a given
 * moment on (ADR 0133).
 */
final readonly class ProspectingHeldExchanges implements HeldExchanges
{
    public function __construct(
        private ConsultHeldExchangesService $consultHeldExchangesService,
        private ConsultPlannedExchangesService $consultPlannedExchangesService,
    ) {
    }

    public function find(AccountId $accountId, ExchangeId $id): ?HeldExchange
    {
        $kept = new KeptHeldExchanges();
        ($this->consultHeldExchangesService)(new ConsultHeldExchanges($accountId->value, [$id->value]), $kept);
        $held = $kept->exchanges[0] ?? null;

        return null === $held ? null : self::heldExchangeOf($held, $accountId);
    }

    public function plannedBy(AccountId $accountId, MemberId $holder, \DateTimeImmutable $from): array
    {
        $kept = new KeptHeldExchanges();
        ($this->consultPlannedExchangesService)(new ConsultPlannedExchanges($accountId->value, $holder->value, $from), $kept);

        return array_map(static fn (HeldExchangeResponse $held): HeldExchange => self::heldExchangeOf($held, $accountId), $kept->exchanges);
    }

    private static function heldExchangeOf(HeldExchangeResponse $held, AccountId $accountId): HeldExchange
    {
        return new HeldExchange(
            new ExchangeId($held->exchangeId),
            $accountId,
            new MemberId($held->holderId),
            ExchangeStatus::from($held->status),
            Channel::tryFrom($held->channel),
            $held->startsAt,
            $held->durationInMinutes,
            $held->place ?? $held->link,
            null === $held->contactId ? null : new PersonId($held->contactId),
            null === $held->organizationId ? null : new OrganizationId($held->organizationId),
            $held->organizationName,
            $held->linkRequested,
            $held->bookedByLink,
        );
    }
}
