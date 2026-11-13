<?php

namespace App\Shared\Messaging;

use Prospecter\Core\Accounts\Domain\Event as Accounts;
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
        'prospecter.personal-data.internal' => [
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
            'ContactDetailsChanged.v1' => Prospecting\ContactDetailsChanged::class,
            'ContactRemoved.v1' => Prospecting\ContactRemoved::class,
            'ContactsLeftWithoutOrganization.v1' => Prospecting\ContactsLeftWithoutOrganization::class,
            'DetachedFromHolding.v1' => Prospecting\DetachedFromHolding::class,
            'ExchangeCancelled.v1' => Prospecting\ExchangeCancelled::class,
            'ExchangeContactForgotten.v1' => Prospecting\ExchangeContactForgotten::class,
            'ExchangeCorrected.v1' => Prospecting\ExchangeCorrected::class,
            'ExchangeErased.v1' => Prospecting\ExchangeErased::class,
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
            'ToDoErased.v1' => Prospecting\ToDoErased::class,
            'ToDoLeftWithoutOrganization.v1' => Prospecting\ToDoLeftWithoutOrganization::class,
            'ToDoNoLongerKeptFor.v1' => Prospecting\ToDoNoLongerKeptFor::class,
            'ToDoNoted.v1' => Prospecting\ToDoNoted::class,
            'ToDoRevised.v1' => Prospecting\ToDoRevised::class,
            'ToDoTickedOff.v1' => Prospecting\ToDoTickedOff::class,
            'ToDoUnticked.v1' => Prospecting\ToDoUnticked::class,
        ],
    ];
}
