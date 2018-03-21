<?php
namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

class MenuBuilder
{
    /**
     * @var FactoryInterface
     */
    protected $factory;
    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
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

        $menu->addChild('Dashboard', ['route' => 'dashboard_display']  + $commonAttributes);
        $menu->addChild('Prospects', ['route' => 'prospect_add']  + $commonAttributes);

        return $menu;
    }
}
