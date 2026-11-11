<?php

namespace App\Shared\Messaging;

use Prospecter\Core\Accounts\Contract\Event as Accounts;
use Prospecter\Core\PersonalData\Contract\Event as PersonalData;
use Prospecter\Core\Prospecting\Contract\Event as Prospecting;

/**
 * The integration events each context publishes in its Contract (ADR 0046, 0047).
 */
final readonly class IntegrationEventTypes extends EventTypes
{
    protected const array TYPES = [
        'prospecter.accounts' => [
            'AccountOpened.v1' => Accounts\AccountOpened::class,
            'MemberRegistered.v1' => Accounts\MemberRegistered::class,
        ],
        'prospecter.personal-data' => [
            'ContactCollected.v1' => PersonalData\ContactCollected::class,
            'ContactForgotten.v1' => PersonalData\ContactForgotten::class,
            'ContactFactsChanged.v1' => PersonalData\ContactFactsChanged::class,
        ],
        'prospecter.prospecting' => [
            'ContactsLeftWithoutOrganization.v1' => Prospecting\ContactsLeftWithoutOrganization::class,
            'OrganizationFactsChanged.v1' => Prospecting\OrganizationFactsChanged::class,
            'ExchangeFactsChanged.v1' => Prospecting\ExchangeFactsChanged::class,
        ],
    ];
}
