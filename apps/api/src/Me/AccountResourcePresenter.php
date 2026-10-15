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
 * My account composed of my membership (Accounts), my profile (PersonalData) and my login email
 * (authentication), with a link per intention they offer me.
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
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links)
    {
    }

    public function present(AccountResponse $account, PersonResponse $me, string $loginEmail): AccountResource
    {
        return new AccountResource(
            loginEmail: $loginEmail,
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
     * Signing in is not the business of the core (ADR 0011): a member may always change their login email
     * and their password.
     */
    private function linksOf(AccountResponse $account, PersonResponse $me): AccountLinksResource
    {
        $possible = [];
        foreach ([
            ...$this->linked($me->possibleIntents, self::PROFILE_ROUTES),
            ...$this->linked($account->accountIntents, self::ORGANIZATION_ROUTES),
        ] as [$name, $route]) {
            $possible[$name] = $this->links->to($route);
        }

        return new AccountLinksResource($this->links->to('api_me_show'), $this->links->to('api_me_change_password'), $this->links->to('api_me_change_login_email'), ...$possible);
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
