<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Application\ConsultHeldExchanges\HeldExchange;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges\HeldExchangeResponse;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The exchanges held, in the published language of Prospecting (ADR 0127), each named by the first organization it
 * is still kept for, one reading per exchange: an exchange kept for no organization any more is about to be erased,
 * absent; one with a contact to attach, kept for none until they are attached (ADR 0146), without organization.
 */
final readonly class PublishedHeldExchanges
{
    public function __construct(private OrganizationSheetRetriever $organizationSheetRetriever)
    {
    }

    /**
     * @param list<HeldExchange> $exchanges
     *
     * @return list<HeldExchangeResponse>
     */
    public function of(array $exchanges): array
    {
        return array_values(array_filter(array_map($this->responseOf(...), $exchanges)));
    }

    private function responseOf(HeldExchange $exchange): ?HeldExchangeResponse
    {
        $organization = $this->firstOrganizationOf($exchange);
        if (null === $organization && !self::isWithAContactToAttach($exchange)) {
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
            $exchange->linkRequested,
            $exchange->contactId,
            $organization?->id,
            null === $organization ? null : $organization->corporateName ?? $organization->fullName,
            $exchange->bookedByLink,
        );
    }

    private static function isWithAContactToAttach(HeldExchange $exchange): bool
    {
        return [] === $exchange->organizationIds && null !== $exchange->contactId;
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
