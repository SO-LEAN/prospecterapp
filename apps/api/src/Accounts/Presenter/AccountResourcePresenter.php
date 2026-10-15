<?php

namespace App\Accounts\Presenter;

use App\Accounts\Resource\AccountLinksResource;
use App\Accounts\Resource\AccountOrganizationResource;
use App\Accounts\Resource\AccountResource;
use App\Accounts\Resource\PersonNameResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LegalIdentityResource;
use Prospecter\Core\Accounts\Application\AccountResponse;

final readonly class AccountResourcePresenter
{
    private const array MY_ACCOUNT_ROUTES = [
        'Rename' => ['changeMyName', 'api_me_change_name'],
        'ChangeUserName' => ['changeMyUserName', 'api_me_change_user_name'],
        'ChangeContactDetails' => ['changeMyContactDetails', 'api_me_change_contact_details'],
        'ChangePicture' => ['changeMyPicture', 'api_me_change_picture'],
    ];

    private const array MY_ORGANIZATION_ROUTES = [
        'ReviseLegalIdentity' => ['reviseMyOrganizationIdentity', 'api_me_revise_organization_identity'],
        'ChangeLogo' => ['changeMyOrganizationLogo', 'api_me_change_organization_logo'],
    ];

    public function __construct(private ImageResourceFactory $images, private Links $links)
    {
    }

    public function present(AccountResponse $response): AccountResource
    {
        return new AccountResource(
            userName: $response->userName,
            name: PersonNameResource::from($response->name),
            contactDetails: ContactDetailsResource::from($response->contactDetails),
            picture: $this->images->create($response->picture?->url),
            organization: new AccountOrganizationResource(
                LegalIdentityResource::from($response->organizationIdentity),
                $this->images->create($response->organizationLogo?->url),
            ),
            links: $this->linksOf($response),
        );
    }

    /**
     * The password is not the business of the core (ADR 0011): a user may always change it. The intents
     * on my organization without an endpoint under /api/me have no link here.
     */
    private function linksOf(AccountResponse $response): AccountLinksResource
    {
        $possible = [];
        foreach ([...$this->linked($response->possibleIntents, self::MY_ACCOUNT_ROUTES), ...$this->linked($response->accountIntents, self::MY_ORGANIZATION_ROUTES)] as [$name, $route]) {
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
