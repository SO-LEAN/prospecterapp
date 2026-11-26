<?php

declare(strict_types=1);

namespace App\Me;

use App\Me\Resource\MyOrganizationLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\Accounts\Application\Account\ConsultMyAccount\ConsultMyAccountOutput;
use Prospecter\Core\Accounts\Application\Account\MyAccount;

/**
 * Presents what Accounts shows of my account: my membership, a link per intent it offers me on my organization, and my
 * team's campaigns, which need the right to manage personal data. One presenter serves one call (ADR 0085).
 */
final class MyOrganizationPresenter implements ConsultMyAccountOutput
{
    private const array ROUTES = [
        'ReviseLegalIdentity' => ['reviseMyOrganizationIdentity', 'api_me_revise_organization_identity'],
        'ChangeLogo' => ['changeMyOrganizationLogo', 'api_me_change_organization_logo'],
        'Relocate' => ['relocateMyOrganization', 'api_me_relocate_organization'],
    ];

    public private(set) MyAccount $account;

    public private(set) MyOrganizationLinksResource $links;

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks)
    {
    }

    public function present(MyAccount $account): void
    {
        $this->account = $account;
        $this->links = new MyOrganizationLinksResource(...[
            ...$this->possible($account->accountIntents),
            'campaigns' => $this->intentLinks->holds(User::PERSONAL_DATA) ? $this->routes->to('api_campaigns_list') : null,
        ]);
    }

    /**
     * @param list<string> $intents
     *
     * @return array<string, LinkResource> by link name
     */
    private function possible(array $intents): array
    {
        $possible = [];

        foreach ($intents as $intent) {
            if (isset(self::ROUTES[$intent])) {
                [$name, $route] = self::ROUTES[$intent];
                $possible[$name] = $this->routes->to($route);
            }
        }

        return $possible;
    }
}
