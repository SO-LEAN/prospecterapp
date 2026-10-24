<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationLinksResource;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\IntentLinks;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\AddressResource;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LinkResource;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * An organization with a link per intention its state offers and the signed-in member may carry out
 * (`IntentLinks`, ADR 0081), and to its timeline (ADR 0076); removing a contact concerns one contact, so its link is on
 * each contact of GET /api/organizations/{id}/contacts.
 */
final readonly class OrganizationResourcePresenter
{
    private const array ROUTES = [
        'reviseLegalIdentity' => [OrganizationVoter::REVISE_LEGAL_IDENTITY, 'api_organizations_revise_legal_identity'],
        'changeContactDetails' => [OrganizationVoter::CHANGE_CONTACT_DETAILS, 'api_organizations_change_contact_details'],
        'relocate' => [OrganizationVoter::RELOCATE, 'api_organizations_relocate'],
        'writeObservations' => [OrganizationVoter::WRITE_OBSERVATIONS, 'api_organizations_write_observations'],
        'attachToHolding' => [OrganizationVoter::ATTACH_TO_HOLDING, 'api_organizations_attach_to_holding'],
        'detachFromHolding' => [OrganizationVoter::DETACH_FROM_HOLDING, 'api_organizations_detach_from_holding'],
        'changeLogo' => [OrganizationVoter::CHANGE_LOGO, 'api_organizations_change_logo'],
        'removeLogo' => [OrganizationVoter::REMOVE_LOGO, 'api_organizations_remove_logo'],
        'addContact' => [OrganizationVoter::ADD_CONTACT, 'api_organizations_add_contact'],
        'stopProspecting' => [OrganizationVoter::STOP_PROSPECTING, 'api_organizations_stop_prospecting'],
    ];

    private const array TIMELINE_ROUTES = [
        'exchanges' => [OrganizationVoter::CONSULT_TIMELINE, 'api_organizations_timeline'],
        'noteExchange' => [OrganizationVoter::NOTE_EXCHANGE, 'api_organizations_note_exchange'],
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links, private IntentLinks $intents, private AuthorizationCheckerInterface $access)
    {
    }

    public function present(OrganizationResponse $response): OrganizationResource
    {
        return new OrganizationResource(
            id: $response->id,
            name: $response->fullName,
            legalIdentity: new LegalIdentityResource($response->corporateName, $response->form, $response->type),
            contactDetails: new ContactDetailsResource($response->email, $response->phoneNumber, $response->language),
            address: AddressResource::orNone($response->street, $response->postalCode, $response->city, $response->country),
            location: LocationResource::orNone($response->latitude, $response->longitude),
            observations: $response->observations,
            logo: $this->images->create($response->logo?->url),
            holdingId: $response->holdingId,
            contactIds: $response->contactIds,
            createdAt: $response->createdAt,
            modifiedAt: $response->modifiedAt,
            links: $this->linksOf($response),
        );
    }

    private function linksOf(OrganizationResponse $response): OrganizationLinksResource
    {
        $id = ['id' => $response->id];
        $granted = [
            ...array_filter(self::ROUTES, fn (array $route): bool => $this->intents->shows($route[0], $response, $response->possibleIntents)),
            ...array_filter(self::TIMELINE_ROUTES, fn (array $route): bool => $this->access->isGranted($route[0], $response)),
        ];

        return new OrganizationLinksResource(
            $this->links->to('api_organizations_show', $id),
            ...array_map(fn (array $route): LinkResource => $this->links->to($route[1], $id), $granted),
        );
    }
}
