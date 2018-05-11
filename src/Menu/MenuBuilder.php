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
            $menu->addChild('Organizations', $commonAttributes + ['route' => 'organization_find', 'extras' => ['current_regex' => '#^/organizations#']] + $commonAttributes);
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
            'childrenAttributes' => ['class' => 'breadcrumb mt-2'],
        ]);

        $commonAttributes = ['currentClass' => 'active ', 'attributes' => ['class' => 'breadcrumb-item']];

        $menu->addChild('', ['route' => 'dashboard_display', 'extras' => $this->configureIcon('fa fa-home')] + $commonAttributes);

        if (preg_match('#^/organizations$#', $this->currentRequest->getPathInfo())) {
            $menu->addChild('Organizations', ['attributes' => ['class' => 'breadcrumb-item active']]);
        } elseif (preg_match('#^/organizations#', $this->currentRequest->getPathInfo())) {
            $menu->addChild('Organizations', ['route' => 'organization_find'] + $commonAttributes);
        }

        if (preg_match('#^/organizations/[0-9]+/view#', $this->currentRequest->getPathInfo())) {
            $menu->addChild('Detail', ['attributes' => ['class' => 'breadcrumb-item active']]);
        }

        if (preg_match('#^/organizations/[0-9]+/update#', $this->currentRequest->getPathInfo())) {
            $menu->addChild('Detail', ['route' => 'organization_view', 'routeParameters' => ['id' => $this->currentRequest->get('id')]] + $commonAttributes);
            $menu->addChild('Edit',  ['attributes' => ['class' => 'breadcrumb-item active']]);

        }

        return $menu;
    }

    /**
     * @param array $options
     * @return ItemInterface
     */
    public function createLeftMenu(array $options)
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => $options['root_class'] ?? null,
            ],
        ]);

        $commonAttributes = $options['child_attributes'] ?? [];
        $extras = $options['extras'] ?? [];

        $menu->addChild('Find organization', ['route' => 'organization_find', 'extras' => $this->configureIcon('fa fa-search') + $extras] + $commonAttributes);
        $menu->addChild('Add organization', ['route' => 'organization_create', 'extras' => $this->configureIcon('fa fa-address-card') + $extras] + $commonAttributes);

        return $menu;
    }

    /**
     * @param array $options
     * @return ItemInterface
     */
    public function createRightMenu(array $options)
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => $options['root_class'] ?? null,
            ],
        ]);

        $commonAttributes = $options['child_attributes'] ?? [];
        $extras = $options['extras'] ?? [];

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
