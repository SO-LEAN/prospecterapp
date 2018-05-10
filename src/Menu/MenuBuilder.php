<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class MenuBuilder.
 */
class MenuBuilder
{
    /**
     * @var FactoryInterface
     */
    protected $factory;
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;
    /**
     * @var Request
     */
    protected $currentRequest;

    /**
     * @param FactoryInterface $factory
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param Request $currentRequest
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authorizationChecker, Request $currentRequest)
    {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
        $this->currentRequest = $currentRequest;
    }

    /**
     * @param array $options
     * @return ItemInterface
     */
    public function createMainMenu(array $options)
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => $options['root_class'] ?? null,
                ],
        ]);
        $commonAttributes = $options['child_attributes'] ?? [];

        if ($this->authorizationChecker->isGranted('ROLE_PROSPECTOR')) {
            $menu->addChild('Dashboard', $commonAttributes + ['route' => 'dashboard_display', 'extras' => ['current_regex' => '#^(/$)|(/dashboard)#']]);
            $menu->addChild('Organization', $commonAttributes + ['route' => 'organization_create', 'extras' => ['current_regex' => '#^/organizations#']] + $commonAttributes);
            $menu->addChild('Prospects', $commonAttributes + ['route' => 'prospect_create', 'extras' => ['current_regex' => '#^/protects#']] + $commonAttributes);
        }
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('Login', ['route' => 'login'] + $commonAttributes);
        }

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createBreadcrumb()
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => ['class' => 'nav navbar-nav'],
        ]);

        $commonAttributes = ['currentClass' => 'active ', 'attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link']];

        if ($this->authorizationChecker->isGranted('ROLE_PROSPECTOR')) {
            $menu->addChild('Dashboard', ['route' => 'dashboard_display'] + $commonAttributes);
            $menu->addChild('Organization', ['route' => 'organization_create'] + $commonAttributes);
            $menu->addChild('Prospects', ['route' => 'prospect_create'] + $commonAttributes);
        }
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('Login', ['route' => 'login'] + $commonAttributes);
        }

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createLeftMenu()
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => ['class' => 'nav flex-column'],
        ]);

        $commonAttributes = ['currentClass' => 'nav-link active', 'attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link']];
        $extras = ['wrap_label' => 'd-none d-xl-inline'];

        $menu->addChild('Add organization', ['route' => 'organization_create',  'extras' => $this->configureIcon('fa fa-address-card') + $extras] + $commonAttributes);
        $menu->addChild('Find organization', ['route' => 'organization_find', 'extras' => $this->configureIcon('fa fa-search') + $extras] + $commonAttributes);

        return $menu;
    }

    /**
     * @return ItemInterface
     */
    public function createRightMenu()
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => ['class' => 'nav flex-column'],
        ]);

        $commonAttributes = ['currentClass' => 'nav-link active', 'attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link']];
        $extras = ['wrap_label' => 'd-none d-xl-inline'];

        $this->createOrganizationViewMenu($menu, $commonAttributes, $extras);

        return $menu;
    }

    /**
     * @param string $icon
     * @return array
     */
    private function configureIcon(string $icon)
    {
        return [
            'icon' => $icon,
        ];
    }

    /**
     * @param ItemInterface $menu
     * @param array         $commonAttributes
     * @param array         $extras
     */
    private function createOrganizationViewMenu(ItemInterface $menu, array $commonAttributes, array $extras): void
    {
        if (preg_match('/^organization_view/', $this->currentRequest->get('_route'))) {
            $id = $this->currentRequest->get('id');
            $menu->addChild('Edit organization', ['route' => 'organization_update',  'routeParameters' => ['id' => $id], 'extras' => $this->configureIcon('fa fa-edit') + $extras] + $commonAttributes);
            $menu->addChild('Add Prospect', ['route' => 'prospect_create', 'extras' => $this->configureIcon('fa fa-user-plus') + $extras] + $commonAttributes);
        }
    }
}
