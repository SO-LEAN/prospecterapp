<?php

namespace App\Prospecting;

use App\Security\AccountResourceVoter;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;

/**
 * What the signed-in member may do with an organization of their account (ADR 0081): its contacts are personal
 * data, so managing them, noting an exchange, or stopping to prospect it while it has some (they may be erased
 * with it) needs the right to manage them.
 *
 * @extends AccountResourceVoter<OrganizationResponse>
 */
final class OrganizationVoter extends AccountResourceVoter
{
    public const string CONSULT = 'ConsultOrganization';
    public const string CONSULT_TIMELINE = 'ConsultTimeline';
    public const string NOTE_EXCHANGE = 'NoteExchange';
    public const string REVISE_LEGAL_IDENTITY = 'ReviseLegalIdentity';
    public const string CHANGE_CONTACT_DETAILS = 'ChangeContactDetails';
    public const string RELOCATE = 'Relocate';
    public const string WRITE_OBSERVATIONS = 'WriteObservations';
    public const string ATTACH_TO_HOLDING = 'AttachToHolding';
    public const string DETACH_FROM_HOLDING = 'DetachFromHolding';
    public const string CHANGE_LOGO = 'ChangeLogo';
    public const string REMOVE_LOGO = 'RemoveLogo';
    public const string ADD_CONTACT = 'AddContact';
    public const string REMOVE_CONTACT = 'RemoveContact';
    public const string STOP_PROSPECTING = 'StopProspecting';
    private const array ON_ITS_CONTACTS = [self::ADD_CONTACT, self::REMOVE_CONTACT, self::NOTE_EXCHANGE];
    private const array ATTRIBUTES = [
        self::CONSULT, self::CONSULT_TIMELINE, self::NOTE_EXCHANGE, self::REVISE_LEGAL_IDENTITY, self::CHANGE_CONTACT_DETAILS,
        self::RELOCATE, self::WRITE_OBSERVATIONS, self::ATTACH_TO_HOLDING, self::DETACH_FROM_HOLDING, self::CHANGE_LOGO,
        self::REMOVE_LOGO, self::ADD_CONTACT, self::REMOVE_CONTACT, self::STOP_PROSPECTING,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof OrganizationResponse && in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function accountOf(object $subject): string
    {
        return $subject->accountId;
    }

    protected function unknownReason(): string
    {
        return 'unknownOrganization';
    }

    protected function needsThePersonalDataRight(string $attribute, object $subject): bool
    {
        return in_array($attribute, self::ON_ITS_CONTACTS, true)
            || (self::STOP_PROSPECTING === $attribute && [] !== $subject->contactIds);
    }
}
