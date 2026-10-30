<?php

namespace App\Me;

use App\Me\Resource\AccountLinksResource;
use App\Me\Resource\AccountOrganizationResource;
use App\Me\Resource\AccountResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\AddressResource;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * My account composed of my membership (Accounts) and my profile (PersonalData), with a link per intention
 * they offer me. What I sign in with lives in the user pool (`/api/me/sign-in`). Built for one call with my
 * membership, it presents my profile and keeps the resource (ADR 0085).
 */
final class AccountResourcePresenter implements ConsultPersonOutput
{
    private const array PROFILE_ROUTES = [
        'Rename' => ['changeMyName', 'api_me_change_name'],
        'ChangeContactDetails' => ['changeMyContactDetails', 'api_me_change_contact_details'],
        'ChangePicture' => ['changeMyPicture', 'api_me_change_picture'],
    ];

    private const array ORGANIZATION_ROUTES = [
        'ReviseLegalIdentity' => ['reviseMyOrganizationIdentity', 'api_me_revise_organization_identity'],
        'ChangeLogo' => ['changeMyOrganizationLogo', 'api_me_change_organization_logo'],
        'Relocate' => ['relocateMyOrganization', 'api_me_relocate_organization'],
    ];

    public private(set) AccountResource $resource;

    public function __construct(private readonly MyAccount $account, private readonly ImageResourceFactory $imageResourceFactory, private readonly Links $links)
    {
    }

    public function present(PersonCard $me): void
    {
        $this->resource = new AccountResource(
            name: new PersonNameResource($me->firstName, $me->lastName),
            contactDetails: new ContactDetailsResource($me->email, $me->phoneNumber, $me->language),
            picture: $this->imageResourceFactory->create($me->picture?->url),
            organization: $this->organization(),
            links: $this->linksOf($me),
        );
    }

    private function organization(): AccountOrganizationResource
    {
        $account = $this->account;

        return new AccountOrganizationResource(
            new LegalIdentityResource($account->organizationCorporateName, $account->organizationForm, $account->organizationType),
            $this->imageResourceFactory->create($account->organizationLogo?->url),
            AddressResource::orNone($account->organizationStreet, $account->organizationPostalCode, $account->organizationCity, $account->organizationCountry),
            LocationResource::orNone($account->organizationLatitude, $account->organizationLongitude),
        );
    }

    private function linksOf(PersonCard $me): AccountLinksResource
    {
        $possible = [];
        foreach ([
            ...$this->linked($me->possibleIntents, self::PROFILE_ROUTES),
            ...$this->linked($this->account->accountIntents, self::ORGANIZATION_ROUTES),
        ] as [$name, $route]) {
            $possible[$name] = $this->links->to($route);
        }

        return new AccountLinksResource($this->links->to('api_me_show'), ...$possible);
    }

    /**
     * @param list<string>                         $intents names of the possible intentions
     * @param array<string, array{string, string}> $routes  link name and route by intent
     *
     * @return list<array{string, string}>
     */
    private function linked(array $intents, array $routes): array
    {
        return array_values(array_filter(array_map(static fn (string $intent): ?array => $routes[$intent] ?? null, $intents)));
    }
}
