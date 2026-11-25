<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Shared\Resource\LinkResource;
use App\Shared\Resource\Offers;
use OpenApi\Attributes as OA;

/**
 * One link for each intention the signed-in user may currently carry out on the organization. A link is absent when the
 * user may not.
 */
final readonly class OrganizationLinksResource
{
    public function __construct(
        public LinkResource $self,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_revise_legal_identity')]
        public ?LinkResource $reviseLegalIdentity = null,
        #[OA\Property(nullable: false, description: 'The establishments from the company directory that it may be, to record its SIRET')]
        public ?LinkResource $searchEstablishments = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_change_contact_details')]
        public ?LinkResource $changeContactDetails = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_relocate')]
        public ?LinkResource $relocate = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_write_observations')]
        public ?LinkResource $writeObservations = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_attach_to_holding')]
        public ?LinkResource $attachToHolding = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_detach_from_holding')]
        public ?LinkResource $detachFromHolding = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_change_logo')]
        public ?LinkResource $changeLogo = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_remove_logo')]
        public ?LinkResource $removeLogo = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_add_contact')]
        public ?LinkResource $addContact = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_stop_prospecting')]
        public ?LinkResource $stopProspecting = null,
        #[OA\Property(nullable: false, description: 'Its timeline: its exchanges and those of its contacts')]
        public ?LinkResource $exchanges = null,
        #[OA\Property(nullable: false)]
        #[Offers('api_organizations_note_exchange')]
        public ?LinkResource $noteExchange = null,
        #[OA\Property(nullable: false, description: 'Its to-dos and those of its contacts (ADR 0113)')]
        public ?LinkResource $toDos = null,
        #[OA\Property(nullable: false, description: 'The tags that members put on it by hand. Campaigns keeps them (ADR 0138)')]
        public ?LinkResource $tags = null,
    ) {
    }
}
