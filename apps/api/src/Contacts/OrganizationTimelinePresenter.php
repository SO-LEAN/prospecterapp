<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Prospecting\Resource\ExchangeResource;
use App\Prospecting\Resource\TimelineLinksResource;
use App\Prospecting\TimelineResourceFactory;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Prospecting\Application\Timeline\OrganizationTimeline;
use Prospecter\Core\Prospecting\Application\Timeline\OrganizationTimelineOutput;

/**
 * Presents what Prospecting shows of a slice of an organization's timeline, or of its exchanges to redact: each
 * exchange with its links, and who took part, by ids. One presenter serves one call (ADR 0085, 0217).
 */
final class OrganizationTimelinePresenter implements OrganizationTimelineOutput
{
    private const array ROUTES = ['api_organizations_timeline', 'api_organizations_note_exchange', 'api_organizations_plan_exchange'];
    private const array UPCOMING_ROUTES = ['api_organizations_upcoming_exchanges', 'api_organizations_note_exchange', 'api_organizations_plan_exchange'];
    private const array TO_REDACT_ROUTES = ['api_organizations_exchanges_to_redact', 'api_organizations_note_exchange', 'api_organizations_plan_exchange'];

    private const string CATCH_UP = 'CatchUpGmail';
    private const string CATCH_UP_ROUTE = 'api_organizations_reimport_gmail';

    public private(set) OrganizationTimeline $timeline;

    /** @var array<string, ExchangeResource> by exchange id */
    public private(set) array $exchanges = [];

    public private(set) TimelineLinksResource $links;

    /**
     * @param array{string, string, string} $routes the list's route, the route that logs an exchange and the route that plans one
     */
    private function __construct(
        private readonly array $routes,
        private readonly TimelineResourceFactory $timelineResourceFactory,
        private readonly Links $routing,
        private readonly ?IntentLinks $intentLinks = null,
    ) {
    }

    public static function ofTheTimeline(TimelineResourceFactory $timelines, Links $links, IntentLinks $intentLinks): self
    {
        return new self(self::ROUTES, $timelines, $links, $intentLinks);
    }

    public static function ofWhatIsToCome(TimelineResourceFactory $timelines, Links $links): self
    {
        return new self(self::UPCOMING_ROUTES, $timelines, $links);
    }

    public static function ofTheExchangesToMakeConform(TimelineResourceFactory $timelines, Links $links): self
    {
        return new self(self::TO_REDACT_ROUTES, $timelines, $links);
    }

    public function present(OrganizationTimeline $organizationTimeline): void
    {
        $this->timeline = $organizationTimeline;
        $timeline = $organizationTimeline->timeline;
        $subject = ['id' => $organizationTimeline->organization->id];

        foreach ($timeline->exchanges as $exchange) {
            $this->exchanges[$exchange->id] = $this->timelineResourceFactory->createExchange($exchange);
        }

        $this->links = $this->timelineResourceFactory->createLinks($this->routes, $subject, $timeline)
            ->withToRedact(0 === $timeline->toMakeConform ? null : $this->routing->to(self::TO_REDACT_ROUTES[0], $subject))
            ->withReimportGmail($this->offersTheCatchUp($organizationTimeline) ? $this->routing->to(self::CATCH_UP_ROUTE, $subject) : null);
    }

    /**
     * Catching up the e-mails exchanged with the organization itself takes the right to manage personal data, because
     * it imports them (ADR 0081, 0176).
     */
    private function offersTheCatchUp(OrganizationTimeline $timeline): bool
    {
        return null !== $this->intentLinks && $this->intentLinks->shows(self::CATCH_UP, $timeline->mailCatchUpIntents, User::PERSONAL_DATA);
    }
}
