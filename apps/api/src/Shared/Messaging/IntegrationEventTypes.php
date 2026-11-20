<?php

namespace App\Shared\Messaging;

use Prospecter\Core\Accounts\Contract\Event as Accounts;
use Prospecter\Core\Booking\Contract\Event as Booking;
use Prospecter\Core\Connectors\Contract\Event as Connectors;
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
        'prospecter.booking' => [
            'SlotBooked.v1' => Booking\SlotBooked::class,
        ],
        'prospecter.connectors' => [
            'VideoCallLinkCreated.v1' => Connectors\VideoCallLinkCreated::class,
            'EmailExchanged.v1' => Connectors\EmailExchanged::class,
        ],
        'prospecter.personal-data' => [
            'BookerIdentified.v1' => PersonalData\BookerIdentified::class,
            'ContactCollected.v1' => PersonalData\ContactCollected::class,
            'ContactForgotten.v1' => PersonalData\ContactForgotten::class,
            'ContactFactsChanged.v2' => PersonalData\ContactFactsChanged::class,
        ],
        'prospecter.prospecting' => [
            'ContactsLeftWithoutOrganization.v1' => Prospecting\ContactsLeftWithoutOrganization::class,
            'ContactLeftUnattached.v1' => Prospecting\ContactLeftUnattached::class,
            'OrganizationFactsChanged.v3' => Prospecting\OrganizationFactsChanged::class,
            'ExchangeFactsChanged.v4' => Prospecting\ExchangeFactsChanged::class,
        ],
    ];

    protected const array FORMER_TYPES = [
        'prospecter.personal-data' => [
            'ContactFactsChanged.v1' => PersonalData\ContactFactsChanged::class,
        ],
        'prospecter.prospecting' => [
            'OrganizationFactsChanged.v1' => Prospecting\OrganizationFactsChanged::class,
            'OrganizationFactsChanged.v2' => Prospecting\OrganizationFactsChanged::class,
            'ExchangeFactsChanged.v1' => Prospecting\ExchangeFactsChanged::class,
            'ExchangeFactsChanged.v2' => Prospecting\ExchangeFactsChanged::class,
            'ExchangeFactsChanged.v3' => Prospecting\ExchangeFactsChanged::class,
        ],
    ];
}
