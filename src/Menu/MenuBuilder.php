<?php
namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class MenuBuilder
 * @package App\Menu
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
     *
     * @return ItemInterface
     */
    public function createMainMenu()
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => ['class' => 'nav navbar-nav'],
        ]);

        $commonAttributes =  ['currentClass' => 'active', 'attributes' =>  ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link']];

        if ($this->authorizationChecker->isGranted('ROLE_PROSPECTOR')) {
            $menu->addChild('Dashboard', ['route' => 'dashboard_display']  + $commonAttributes);
            $menu->addChild('Prospects', ['route' => 'prospect_add']  + $commonAttributes);
        }
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('Login', ['route' => 'login'] + $commonAttributes);
        } else {
            $menu->addChild('Logout', ['route' => 'logout'] + $commonAttributes);
        }

        return $menu;
    }
}
