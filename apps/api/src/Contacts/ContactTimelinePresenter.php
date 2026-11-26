<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimelineIntents\ConsultTimelineIntentsOutput;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimelineIntents\TimelineIntentsResponse;

/**
 * Presents what Prospecting offers on a contact's timeline: the link to their exchanges, and to their to-dos. Reading
 * a contact's to-dos takes the right to manage personal data, because their text may hold some. One presenter serves
 * one call (ADR 0085).
 */
final class ContactTimelinePresenter implements ConsultTimelineIntentsOutput
{
    private const string CONSULT_TIMELINE = 'ConsultTimeline';

    public private(set) ?LinkResource $exchanges = null;

    public private(set) ?LinkResource $toDos = null;

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks, private readonly string $contactId)
    {
    }

    public function present(TimelineIntentsResponse $intents): void
    {
        $id = ['id' => $this->contactId];
        $this->exchanges = in_array(self::CONSULT_TIMELINE, $intents->possibleIntents, true) ? $this->routes->to('api_contacts_timeline', $id) : null;
        $this->toDos = $this->intentLinks->shows(self::CONSULT_TIMELINE, $intents->possibleIntents, User::PERSONAL_DATA) ? $this->routes->to('api_contacts_to_dos', $id) : null;
    }
}
