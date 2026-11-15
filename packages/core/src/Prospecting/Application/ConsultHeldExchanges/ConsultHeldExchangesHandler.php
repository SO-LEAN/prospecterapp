<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultHeldExchanges;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\HeldExchangeRetriever;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchanges;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesOutput;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\ConsultHeldExchangesService;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\HeldExchangeResponse;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The exchanges, each named by the first organization it is still kept for: one reading per exchange, a client asking
 * about one at a time. An exchange kept for no organization any more is about to be erased: absent.
 */
final readonly class ConsultHeldExchangesHandler implements QueryHandler, ConsultHeldExchangesService
{
    public function __construct(
        private HeldExchangeRetriever $heldExchangeRetriever,
        private OrganizationSheetRetriever $organizationSheetRetriever,
    ) {
    }

    public function __invoke(ConsultHeldExchanges $query, ConsultHeldExchangesOutput $output): void
    {
        $held = $this->heldExchangeRetriever->ofIds(new AccountId($query->accountId), array_map(static fn (string $id): ExchangeId => new ExchangeId($id), $query->exchangeIds));

        $output->present(array_values(array_filter(array_map($this->responseOf(...), $held))));
    }

    private function responseOf(HeldExchange $exchange): ?HeldExchangeResponse
    {
        $organization = $this->firstOrganizationOf($exchange);
        if (null === $organization) {
            return null;
        }

        return new HeldExchangeResponse(
            $exchange->id,
            $exchange->holderId,
            $exchange->status,
            $exchange->channel,
            $exchange->startsAt,
            $exchange->durationInMinutes,
            $exchange->place,
            $exchange->link,
            $exchange->contactId,
            $organization->id,
            $organization->corporateName ?? $organization->fullName,
        );
    }

    /**
     * An organization the account stopped prospecting is gone before its exchanges stop being kept for it.
     */
    private function firstOrganizationOf(HeldExchange $exchange): ?OrganizationSheet
    {
        foreach ($exchange->organizationIds as $id) {
            try {
                return $this->organizationSheetRetriever->get(new OrganizationId($id));
            } catch (UnknownOrganization) {
                continue;
            }
        }

        return null;
    }
}
