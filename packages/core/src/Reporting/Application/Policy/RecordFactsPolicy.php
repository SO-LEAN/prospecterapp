<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\Policy;

use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Reporting\Application\RecordContactFacts\RecordContactFacts;
use Prospecter\Core\Reporting\Application\RecordExchangeFacts\RecordExchangeFacts;
use Prospecter\Core\Reporting\Application\RecordOrganizationFacts\RecordOrganizationFacts;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When the facts of an organization, an exchange or a contact change, Reporting records them to count them
 * (ADR 0099).
 */
final readonly class RecordFactsPolicy implements Policy
{
    public function __invoke(OrganizationFactsChanged|ExchangeFactsChanged|ContactFactsChanged $event): RecordOrganizationFacts|RecordExchangeFacts|RecordContactFacts
    {
        return match (true) {
            $event instanceof OrganizationFactsChanged => new RecordOrganizationFacts(
                accountId: $event->accountId,
                organizationId: $event->organizationId,
                revision: $event->revision,
                present: $event->present,
                located: $event->located,
                executedAt: $event->occurredAt,
            ),
            $event instanceof ExchangeFactsChanged => new RecordExchangeFacts(
                accountId: $event->accountId,
                exchangeId: $event->exchangeId,
                revision: $event->revision,
                present: $event->present,
                status: $event->status,
                notedAt: $event->notedAt,
                executedAt: $event->occurredAt,
            ),
            $event instanceof ContactFactsChanged => new RecordContactFacts(
                accountId: $event->accountId,
                contactId: $event->personId,
                revision: $event->revision,
                present: $event->present,
                executedAt: $event->occurredAt,
            ),
        };
    }
}
