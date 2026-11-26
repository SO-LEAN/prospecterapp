<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\AccountLinksResource;
use App\Me\Resource\AccountOrganizationResource;
use App\Me\Resource\AccountResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\AddressResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\Account\MyAccount;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPerson;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Composes my account (ADR 0217). Accounts reads my membership and my organization, with what it offers me on them.
 * PersonalData reads my profile under the person id Accounts gives, with what it offers me on it. What I sign in with
 * is read separately (`/api/me/sign-in`). A command returns nothing, so a controller reads my account again after it
 * to answer with the fresh resource.
 */
final readonly class MyAccountComposition
{
    public function __construct(
        private QueryHandler $consultMyAccount,
        private QueryHandler $consultPerson,
        private ImageResourceFactory $images,
        private Links $links,
        private IntentLinks $intentLinks,
    ) {
    }

    public function read(string $me): AccountResource
    {
        $organization = new MyOrganizationPresenter($this->links, $this->intentLinks);
        $this->consultMyAccount->__invoke(new ConsultMyAccount($me), $organization);
        $profile = new MyProfilePresenter($this->links);
        $this->consultPerson->__invoke(new ConsultPerson($me, $organization->account->personId), $profile);
        $card = $profile->me;

        return new AccountResource(
            name: new PersonNameResource($card->firstName, $card->lastName),
            contactDetails: PersonContactDetailsResource::of($card),
            picture: $this->images->create($card->picture?->url),
            organization: $this->organizationOf($organization->account),
            links: new AccountLinksResource(
                self: $this->links->to('api_me_show'),
                changeMyName: $profile->links->changeMyName,
                changeMyContactDetails: $profile->links->changeMyContactDetails,
                changeMyPicture: $profile->links->changeMyPicture,
                reviseMyOrganizationIdentity: $organization->links->reviseMyOrganizationIdentity,
                changeMyOrganizationLogo: $organization->links->changeMyOrganizationLogo,
                relocateMyOrganization: $organization->links->relocateMyOrganization,
                campaigns: $organization->links->campaigns,
            ),
        );
    }

    private function organizationOf(MyAccount $account): AccountOrganizationResource
    {
        return new AccountOrganizationResource(
            new LegalIdentityResource($account->organizationCorporateName, $account->organizationForm, $account->organizationType, null),
            $this->images->create($account->organizationLogo?->url),
            AddressResource::orNone($account->organizationStreet, $account->organizationPostalCode, $account->organizationCity, $account->organizationCountry),
            LocationResource::orNone($account->organizationLatitude, $account->organizationLongitude),
        );
    }
}
