<?php

namespace App\Me;

use App\Me\Resource\AccountLinksResource;
use App\Me\Resource\AccountOrganizationResource;
use App\Me\Resource\AccountResource;
use App\Me\Resource\PersonNameResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\PersonalData\Application\PersonResponse;

/**
 * My account composed of my membership (Accounts) and my profile (PersonalData), with a link per intention
 * the two contexts offer me.
 */
final readonly class AccountResourcePresenter
{
    private const array MEMBERSHIP_ROUTES = [
        'ChangeUserName' => ['changeMyUserName', 'api_me_change_user_name'],
    ];

    private const array PROFILE_ROUTES = [
        'Rename' => ['changeMyName', 'api_me_change_name'],
        'ChangeContactDetails' => ['changeMyContactDetails', 'api_me_change_contact_details'],
        'ChangePicture' => ['changeMyPicture', 'api_me_change_picture'],
    ];

    private const array ORGANIZATION_ROUTES = [
        'ReviseLegalIdentity' => ['reviseMyOrganizationIdentity', 'api_me_revise_organization_identity'],
        'ChangeLogo' => ['changeMyOrganizationLogo', 'api_me_change_organization_logo'],
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links)
    {
    }

    public function present(AccountResponse $account, PersonResponse $me): AccountResource
    {
        return new AccountResource(
            userName: $account->userName,
            name: new PersonNameResource($me->firstName, $me->lastName),
            contactDetails: new ContactDetailsResource($me->email, $me->phoneNumber, $me->language),
            picture: $this->images->create($me->picture?->url),
            organization: new AccountOrganizationResource(
                LegalIdentityResource::from($account->organizationIdentity),
                $this->images->create($account->organizationLogo?->url),
            ),
            links: $this->linksOf($account, $me),
        );
    }

    /**
     * The password is not the business of the core (ADR 0011): a user may always change it.
     */
    private function linksOf(AccountResponse $account, PersonResponse $me): AccountLinksResource
    {
        $possible = [];
        foreach ([
            ...$this->linked($account->possibleIntents, self::MEMBERSHIP_ROUTES),
            ...$this->linked($me->possibleIntents, self::PROFILE_ROUTES),
            ...$this->linked($account->accountIntents, self::ORGANIZATION_ROUTES),
        ] as [$name, $route]) {
            $possible[$name] = $this->links->to($route);
        }

        return new AccountLinksResource($this->links->to('api_me_show'), $this->links->to('api_me_change_password'), ...$possible);
    }

    /**
     * @param list<\UnitEnum>                      $intents
     * @param array<string, array{string, string}> $routes  link name and route by intent
     *
     * @return list<array{string, string}>
     */
    private function linked(array $intents, array $routes): array
    {
        return array_values(array_filter(array_map(static fn (\UnitEnum $intent): ?array => $routes[$intent->name] ?? null, $intents)));
    }
}
