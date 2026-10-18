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
use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\PersonalData\Application\PersonResponse;

/**
 * My account composed of my membership (Accounts) and my profile (PersonalData), with a link per intention
 * they offer me. What I sign in with lives in the user pool (`/api/me/sign-in`).
 */
final readonly class AccountResourcePresenter
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

    public function __construct(private ImageResourceFactory $images, private Links $links)
    {
    }

    public function present(AccountResponse $account, PersonResponse $me): AccountResource
    {
        return new AccountResource(
            name: new PersonNameResource($me->firstName, $me->lastName),
            contactDetails: new ContactDetailsResource($me->email, $me->phoneNumber, $me->language),
            picture: $this->images->create($me->picture?->url),
            organization: new AccountOrganizationResource(
                new LegalIdentityResource($account->organizationCorporateName, $account->organizationForm, $account->organizationType),
                $this->images->create($account->organizationLogo?->url),
                AddressResource::orNone($account->organizationStreet, $account->organizationPostalCode, $account->organizationCity, $account->organizationCountry),
                LocationResource::orNone($account->organizationLatitude, $account->organizationLongitude),
            ),
            links: $this->linksOf($account, $me),
        );
    }

    private function linksOf(AccountResponse $account, PersonResponse $me): AccountLinksResource
    {
        $possible = [];
        foreach ([
            ...$this->linked($me->possibleIntents, self::PROFILE_ROUTES),
            ...$this->linked($account->accountIntents, self::ORGANIZATION_ROUTES),
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
