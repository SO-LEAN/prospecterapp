<?php

namespace App\Shared\Messaging;

use Prospecter\Core\Accounts\Domain\Event as Accounts;
use Prospecter\Core\Booking\Domain\Event as Booking;
use Prospecter\Core\Campaigns\Domain\Event as Campaigns;
use Prospecter\Core\Connectors\Domain\Event as Connectors;
use Prospecter\Core\PersonalData\Domain\Event as PersonalData;
use Prospecter\Core\Prospecting\Domain\Event as Prospecting;

/**
 * The domain events, each from its own context, never read by another one (ADR 0046).
 */
final readonly class DomainEventTypes extends EventTypes
{
    protected const array TYPES = [
        'prospecter.accounts.internal' => [
            'AccountIdentityRevised.v1' => Accounts\AccountIdentityRevised::class,
            'AccountLogoChanged.v1' => Accounts\AccountLogoChanged::class,
            'AccountOpened.v1' => Accounts\AccountOpened::class,
            'AccountRelocated.v1' => Accounts\AccountRelocated::class,
            'MemberRegistered.v1' => Accounts\MemberRegistered::class,
            'RemindersChanged.v1' => Accounts\RemindersChanged::class,
        ],
        'prospecter.booking.internal' => [
            'BookingClosed.v1' => Booking\BookingClosed::class,
            'BookingLinkChanged.v1' => Booking\BookingLinkChanged::class,
            'BookingOpened.v1' => Booking\BookingOpened::class,
            'ChannelsOffered.v1' => Booking\ChannelsOffered::class,
            'InactivePeriodAdded.v1' => Booking\InactivePeriodAdded::class,
            'InactivePeriodRemoved.v1' => Booking\InactivePeriodRemoved::class,
            'WindowsSet.v1' => Booking\WindowsSet::class,
        ],
        'prospecter.campaigns.internal' => [
            'CampaignDiscarded.v1' => Campaigns\CampaignDiscarded::class,
            'CampaignFrozen.v1' => Campaigns\CampaignFrozen::class,
            'CampaignPrepared.v1' => Campaigns\CampaignPrepared::class,
            'CampaignRecipientsChanged.v1' => Campaigns\CampaignRecipientsChanged::class,
            'CampaignRevised.v1' => Campaigns\CampaignRevised::class,
            'CampaignStarted.v1' => Campaigns\CampaignStarted::class,
            'CampaignStopped.v1' => Campaigns\CampaignStopped::class,
            'ContactTagsChanged.v1' => Campaigns\ContactTagsChanged::class,
            'RecipientDismissed.v1' => Campaigns\RecipientDismissed::class,
            'OrganizationTagsChanged.v1' => Campaigns\OrganizationTagsChanged::class,
        ],
        'prospecter.connectors.internal' => [
            'Connected.v1' => Connectors\Connected::class,
            'CalendarRecreated.v1' => Connectors\CalendarRecreated::class,
            'ConnectionLost.v1' => Connectors\ConnectionLost::class,
            'Disconnected.v1' => Connectors\Disconnected::class,
            'ClientConfigured.v1' => Connectors\ClientConfigured::class,
            'ClientRemoved.v1' => Connectors\ClientRemoved::class,
            'VideoCallLinkCreated.v1' => Connectors\VideoCallLinkCreated::class,
            'MailImportStarted.v1' => Connectors\MailImportStarted::class,
            'MailImportStopped.v1' => Connectors\MailImportStopped::class,
            'MailImportLost.v1' => Connectors\MailImportLost::class,
            'MailWatchRenewed.v1' => Connectors\MailWatchRenewed::class,
        ],
        'prospecter.personal-data.internal' => [
            'CivilityChanged.v1' => PersonalData\CivilityChanged::class,
            'ContactCollected.v1' => PersonalData\ContactCollected::class,
            'ContactForgotten.v1' => PersonalData\ContactForgotten::class,
            'JobTitleChanged.v1' => PersonalData\JobTitleChanged::class,
            'PersonContactDetailsChanged.v1' => PersonalData\PersonContactDetailsChanged::class,
            'PersonPictureChanged.v1' => PersonalData\PersonPictureChanged::class,
            'PersonRenamed.v1' => PersonalData\PersonRenamed::class,
        ],
        'prospecter.prospecting.internal' => [
            'AttachedToHolding.v1' => Prospecting\AttachedToHolding::class,
            'AttachmentRemoved.v1' => Prospecting\AttachmentRemoved::class,
            'ContactAdded.v1' => Prospecting\ContactAdded::class,
            'ContactLeftUnattached.v1' => Prospecting\ContactLeftUnattached::class,
            'ContactToAttachAttached.v1' => Prospecting\ContactToAttachAttached::class,
            'ContactToAttachMet.v1' => Prospecting\ContactToAttachMet::class,
            'ContactToAttachMetAgain.v1' => Prospecting\ContactToAttachMetAgain::class,
            'ContactDetailsChanged.v1' => Prospecting\ContactDetailsChanged::class,
            'ContactRemoved.v1' => Prospecting\ContactRemoved::class,
            'ContactsLeftWithoutOrganization.v1' => Prospecting\ContactsLeftWithoutOrganization::class,
            'DetachedFromHolding.v1' => Prospecting\DetachedFromHolding::class,
            'ExchangeCancelled.v1' => Prospecting\ExchangeCancelled::class,
            'ExchangeContactForgotten.v1' => Prospecting\ExchangeContactForgotten::class,
            'ExchangeCorrected.v1' => Prospecting\ExchangeCorrected::class,
            'ExchangeErased.v3' => Prospecting\ExchangeErased::class,
            'ExchangeKeptFor.v1' => Prospecting\ExchangeKeptFor::class,
            'ExchangeLeftWithoutOrganization.v1' => Prospecting\ExchangeLeftWithoutOrganization::class,
            'ExchangeMadeConform.v1' => Prospecting\ExchangeMadeConform::class,
            'ExchangeMarkedDone.v1' => Prospecting\ExchangeMarkedDone::class,
            'ExchangeMarkedMissed.v1' => Prospecting\ExchangeMarkedMissed::class,
            'ExchangeMoved.v1' => Prospecting\ExchangeMoved::class,
            'ExchangeNoLongerKeptFor.v1' => Prospecting\ExchangeNoLongerKeptFor::class,
            'ExchangeNoted.v1' => Prospecting\ExchangeNoted::class,
            'ExchangePlanned.v1' => Prospecting\ExchangePlanned::class,
            'ExchangeTextCorrected.v1' => Prospecting\ExchangeTextCorrected::class,
            'ExchangeVenueCorrected.v1' => Prospecting\ExchangeVenueCorrected::class,
            'FileAttached.v1' => Prospecting\FileAttached::class,
            'LegalIdentityRevised.v1' => Prospecting\LegalIdentityRevised::class,
            'LogoChanged.v1' => Prospecting\LogoChanged::class,
            'LogoRemoved.v1' => Prospecting\LogoRemoved::class,
            'ObservationsWritten.v1' => Prospecting\ObservationsWritten::class,
            'ProspectingStarted.v1' => Prospecting\ProspectingStarted::class,
            'ProspectingStopped.v1' => Prospecting\ProspectingStopped::class,
            'Relocated.v1' => Prospecting\Relocated::class,
            'LocatedAgain.v1' => Prospecting\LocatedAgain::class,
            'ToDoErased.v1' => Prospecting\ToDoErased::class,
            'ToDoKeptFor.v1' => Prospecting\ToDoKeptFor::class,
            'ToDoLeftWithoutOrganization.v1' => Prospecting\ToDoLeftWithoutOrganization::class,
            'ToDoNoLongerKeptFor.v1' => Prospecting\ToDoNoLongerKeptFor::class,
            'ToDoNoted.v1' => Prospecting\ToDoNoted::class,
            'ToDoRevised.v1' => Prospecting\ToDoRevised::class,
            'ToDoTickedOff.v1' => Prospecting\ToDoTickedOff::class,
            'ToDoUnticked.v1' => Prospecting\ToDoUnticked::class,
            'VideoCallLinkReceived.v1' => Prospecting\VideoCallLinkReceived::class,
        ],
    ];

    protected const array FORMER_TYPES = [
        'prospecter.prospecting.internal' => [
            'ExchangeErased.v1' => Prospecting\ExchangeErased::class,
            'ExchangeErased.v2' => Prospecting\ExchangeErased::class,
        ],
    ];
}
