<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\AccountLinksResource;
use App\Me\Resource\AccountOrganizationResource;
use App\Me\Resource\AccountResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\AddressResource;
use App\Shared\Resource\LegalIdentityResource;
use App\Shared\Resource\LocationResource;
use Prospecter\Core\Accounts\Application\Account\MyAccount;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * Presents my account: my membership (Accounts) and my profile (PersonalData), with one link per intent that they
 * offer me. What I sign in with lives in the user pool (`/api/me/sign-in`). Each call builds this presenter with my
 * membership. The presenter then presents my profile and keeps the resource (ADR 0085).
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

    /**
     * The links to the team's gestures, with the role that each controller requires.
     */
    private const array TEAM_ROUTES = [
        'campaigns' => ['api_campaigns_list', User::PERSONAL_DATA],
    ];

    public function __construct(private readonly MyAccount $account, private readonly ImageResourceFactory $imageResourceFactory, private readonly Links $links, private readonly IntentLinks $intentLinks)
    {
    }

    public function present(PersonCard $me): void
    {
        $this->resource = new AccountResource(
            name: new PersonNameResource($me->firstName, $me->lastName),
            contactDetails: PersonContactDetailsResource::of($me),
            picture: $this->imageResourceFactory->create($me->picture?->url),
            organization: $this->organization(),
            links: $this->linksOf($me),
        );
    }

    private function organization(): AccountOrganizationResource
    {
        $account = $this->account;

        return new AccountOrganizationResource(
            new LegalIdentityResource($account->organizationCorporateName, $account->organizationForm, $account->organizationType, null),
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

        foreach (self::TEAM_ROUTES as $name => [$route, $role]) {
            if ($this->intentLinks->holds($role)) {
                $possible[$name] = $this->links->to($route);
            }
        }

        return new AccountLinksResource($this->links->to('api_me_show'), ...$possible);
    }

    /**
     * @param list<string>                         $intents the possible intents' names
     * @param array<string, array{string, string}> $routes  the link's name and route, by intent
     *
     * @return list<array{string, string}>
     */
    private function linked(array $intents, array $routes): array
    {
        return array_values(array_filter(array_map(static fn (string $intent): ?array => $routes[$intent] ?? null, $intents)));
    }
}
