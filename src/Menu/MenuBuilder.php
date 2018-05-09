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
     * @return ItemInterface
     */
    public function createMainMenu()
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => ['class' => 'nav navbar-nav'],
        ]);

        $commonAttributes = ['currentClass' => 'active', 'attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link']];

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

        $menu->addChild('Add organization', ['route' => 'organization_create',  'extras' => $this->configureIcon('fa fa-industry')] + $commonAttributes);
        $menu->addChild('Find organization', ['route' => 'organization_find', 'extras' => $this->configureIcon('fa fa-industry')] + $commonAttributes);

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

        $this->createOrganizationViewMenu($menu, $commonAttributes);

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
     * @param $menu
     * @param $commonAttributes
     */
    private function createOrganizationViewMenu(ItemInterface $menu, array $commonAttributes): void
    {
        if (preg_match('/^organization_view/', $this->currentRequest->get('_route'))) {
            $id = $this->currentRequest->get('id');
            $menu->addChild('Edit organization', ['route' => 'organization_update',  'routeParameters' => ['id' => $id], 'extras' => $this->configureIcon('fa fa-edit')] + $commonAttributes);
            $menu->addChild('Add Prospect', ['route' => 'prospect_create', 'extras' => $this->configureIcon('fa fa-user-plus')] + $commonAttributes);
        }
    }
}
