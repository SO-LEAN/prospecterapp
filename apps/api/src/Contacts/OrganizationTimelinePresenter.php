<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\InterlocutorResource;
use App\Contacts\Resource\OrganizationExchangeResource;
use App\Contacts\Resource\OrganizationTimelineResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Timeline\Timeline;
use Prospecter\Core\Prospecting\Application\Timeline\TimelineOutput;

/**
 * Presents a slice of an organization's timeline, or of its exchanges to redact. Each exchange names who took part:
 * the person, or the organization itself. The person may still be its contact, or may be removed or forgotten. One
 * presenter serves one call. It gets the slice's persons and keeps the resource (ADR 0085).
 */
final class OrganizationTimelinePresenter implements TimelineOutput
{
    private const array ROUTES = ['api_organizations_timeline', 'api_organizations_note_exchange', 'api_organizations_plan_exchange'];
    private const array UPCOMING_ROUTES = ['api_organizations_upcoming_exchanges', 'api_organizations_note_exchange', 'api_organizations_plan_exchange'];
    private const array TO_REDACT_ROUTES = ['api_organizations_exchanges_to_redact', 'api_organizations_note_exchange', 'api_organizations_plan_exchange'];

    private const string CATCH_UP = 'CatchUpGmail';
    private const string CATCH_UP_ROUTE = 'api_organizations_reimport_gmail';

    public private(set) OrganizationTimelineResource $resource;

    /**
     * @param array{string, string, string} $routes         the list's route, the route that logs an exchange and the route that plans one
     * @param array<string, PersonCard>     $persons        the slice's persons, by id
     * @param list<string>                  $catchUpIntents what Connectors offers to do with the e-mails exchanged with the organization itself, on its timeline only
     */
    private function __construct(
        private readonly array $routes,
        private readonly OrganizationSheet $organization,
        private readonly array $persons,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly Links $links,
        private readonly array $catchUpIntents = [],
        private readonly ?IntentLinks $intentLinks = null,
    ) {
    }

    /**
     * @param array<string, PersonCard> $persons        the slice's persons, by id
     * @param list<string>              $catchUpIntents what Connectors offers to do with the e-mails exchanged with the organization itself
     */
    public static function ofTheTimeline(OrganizationSheet $organization, array $persons, array $catchUpIntents, TimelineResourceFactory $timelines, Links $links, IntentLinks $intentLinks): self
    {
        return new self(self::ROUTES, $organization, $persons, $timelines, $links, $catchUpIntents, $intentLinks);
    }

    /**
     * @param array<string, PersonCard> $persons the persons of the slice, by id
     */
    public static function ofWhatIsToCome(OrganizationSheet $organization, array $persons, TimelineResourceFactory $timelines, Links $links): self
    {
        return new self(self::UPCOMING_ROUTES, $organization, $persons, $timelines, $links);
    }

    /**
     * @param array<string, PersonCard> $persons the persons of the slice, by id
     */
    public static function ofTheExchangesToMakeConform(OrganizationSheet $organization, array $persons, TimelineResourceFactory $timelines, Links $links): self
    {
        return new self(self::TO_REDACT_ROUTES, $organization, $persons, $timelines, $links);
    }

    public function present(Timeline $timeline): void
    {
        $subject = ['id' => $this->organization->id];

        $this->resource = new OrganizationTimelineResource(
            items: array_map(fn (ExchangeInTimeline $exchange): OrganizationExchangeResource => new OrganizationExchangeResource($this->timelineResourceFactory->createExchange($exchange), $this->interlocutorOf($exchange)), $timeline->exchanges),
            toRedact: $timeline->toMakeConform,
            links: $this->timelineResourceFactory->createLinks($this->routes, $subject, $timeline)
                ->withToRedact(0 === $timeline->toMakeConform ? null : $this->links->to(self::TO_REDACT_ROUTES[0], $subject))
                ->withReimportGmail($this->offersTheCatchUp() ? $this->links->to(self::CATCH_UP_ROUTE, $subject) : null),
        );
    }

    /**
     * Catching up the e-mails exchanged with the organization itself takes the right to manage personal data, because
     * it imports them (ADR 0081, 0176).
     */
    private function offersTheCatchUp(): bool
    {
        return null !== $this->intentLinks && $this->intentLinks->shows(self::CATCH_UP, $this->catchUpIntents, User::PERSONAL_DATA);
    }

    private function interlocutorOf(ExchangeInTimeline $exchange): InterlocutorResource
    {
        return null === $exchange->personId
            ? InterlocutorResource::ofTheOrganization($this->organization)
            : InterlocutorResource::ofPerson($exchange->personId, $this->persons[$exchange->personId] ?? null, $exchange->contactPresence);
    }
}
