<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
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
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
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
    public function createSecondaryMenu()
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => ['class' => 'nav flex-column'],
        ]);

        $commonAttributes = ['currentClass' => 'nav-link active', 'attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link']];

        $menu->addChild('Add Organization', ['route' => 'organization_create', 'icon' => 'fa fa-user-plus'] + $commonAttributes);
        $menu->addChild('Add Prospect', ['route' => 'prospect_create', 'icon' => 'fa fa-industry'] + $commonAttributes);

        return $menu;
    }
}
