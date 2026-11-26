<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ToAttachResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Prospecting\Application\Contact\ConsultContactToAttach\ConsultContactToAttachOutput;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;

/**
 * Presents what Prospecting says of a contact who waits for the reader to attach them: until when they wait (ADR 0146),
 * and the organization proposed to attach them to. The proposal needs the right to manage personal data, as attaching
 * the contact does. One presenter serves one call (ADR 0085).
 */
final class ContactToAttachPresenter implements ConsultContactToAttachOutput
{
    public private(set) ?ToAttachResource $toAttach = null;

    public private(set) ?LinkResource $proposedOrganization = null;

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks, private readonly string $contactId)
    {
    }

    public function present(?ContactToAttachInList $contactToAttach): void
    {
        if (null === $contactToAttach) {
            return;
        }

        $this->toAttach = new ToAttachResource($contactToAttach->lastMeetingAt, $contactToAttach->erasableFrom);
        $this->proposedOrganization = $this->intentLinks->holds(User::PERSONAL_DATA) ? $this->routes->to('api_contacts_proposed_organization', ['id' => $this->contactId]) : null;
    }
}
