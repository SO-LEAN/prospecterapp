<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilder
{
    public function __construct(
        protected readonly FactoryInterface $factory,
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
        protected readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => $options['root_class'] ?? null,
            ],
        ]);
        $commonAttributes = $options['child_attributes'] ?? [];

        if ($this->authorizationChecker->isGranted('ROLE_PROSPECTOR')) {
            $menu->addChild(
                'Dashboard',
                $commonAttributes + ['route' => 'dashboard_display', 'extras' => ['current_regex' => '#^(/$)|(/dashboard)#']]
            );
            $menu->addChild(
                'Organizations',
                $commonAttributes + ['route' => 'organization_find', 'extras' => ['current_regex' => '#^/organizations#']] + $commonAttributes
            );
            $menu->addChild(
                'Prospects',
                $commonAttributes + ['route' => 'prospect_create', 'extras' => ['current_regex' => '#^/protects#']] + $commonAttributes
            );
        }
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('Login', ['route' => 'login'] + $commonAttributes);
        }

        return $menu;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createBreadcrumb(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => $options['root_class'] ?? null,
            ],
        ]);

        $commonAttributes = $options['child_attributes'] ?? [];
        $extras = $options['extras'] ?? [];
        $active = ['class' => 'breadcrumb-item active'];
        $menu->addChild(
            'Dashboard',
            ['route' => 'dashboard_display', 'extras' => ['wrap_label' => 'd-none'] + $this->getIcon('fa fa-home')] + $commonAttributes
        );

        if ($this->isMatchedRequest('#^/organizations$#')) {
            $menu->addChild('Organizations', ['attributes' => $active, 'extras' => $extras + $this->getIcon('fa fa-search d-md-none')]);
        } elseif ($this->isMatchedRequest('#^/organizations#')) {
            $menu->addChild('Organizations', ['route' => 'organization_find', 'extras' => $extras + $this->getIcon('fa fa-search d-md-none')] + $commonAttributes);
        }

        if ($this->isMatchedRequest('#^/organizations/[0-9]+/view#')) {
            $menu->addChild('Detail', ['attributes' => $active, 'extras' => $extras + $this->getIcon('fa fa-building-o d-md-none')]);
        }

        if ($this->isMatchedRequest('#^/organizations/[0-9]+/update#')) {
            $menu->addChild(
                'Detail',
                ['route' => 'organization_view', 'routeParameters' => ['id' => $this->getCurrentRequest()->attributes->get('id')], 'extras' => $extras + $this->getIcon('fa fa-building-o d-md-none')] + $commonAttributes
            );
            $menu->addChild('Edit', ['attributes' => $active, 'extras' => $extras + $this->getIcon('fa fa-edit d-md-none')]);
        }

        if ($this->isMatchedRequest('#^/organizations/add#')) {
            $menu->addChild('Add', ['attributes' => $active, 'extras' => $extras + $this->getIcon('fa fa-address-card d-md-none')]);
        }

        return $menu;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createLeftMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => $options['root_class'] ?? null,
            ],
        ]);

        $commonAttributes = $options['child_attributes'] ?? [];
        $extras = $options['extras'] ?? [];

        $menu->addChild(
            'Find organization',
            ['route' => 'organization_find', 'extras' => $extras + $this->getIcon('fa fa-search')] + $commonAttributes
        );
        $menu->addChild(
            'Add organization',
            ['route' => 'organization_create', 'extras' => $extras + $this->getIcon('fa fa-address-card')] + $commonAttributes
        );

        return $menu;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createRightMenu(array $options): ItemInterface
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
     * @return array<string, string>
     */
    private function getIcon(string $icon): array
    {
        return [
            'icon' => $icon,
        ];
    }

    private function isMatchedRequest(string $pattern): bool
    {
        return (bool) preg_match($pattern, $this->getCurrentRequest()->getPathInfo());
    }

    private function getCurrentRequest(): Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function createOrganizationViewMenu(ItemInterface $menu, array $commonAttributes, array $extras): void
    {
        if (preg_match('/^organization_view/', (string) $this->getCurrentRequest()->attributes->get('_route'))) {
            $id = $this->getCurrentRequest()->attributes->get('id');
            $menu->addChild(
                'Edit organization',
                [
                    'route' => 'organization_update',
                    'routeParameters' => ['id' => $id],
                    'extras' => $this->getIcon('fa fa-edit') + $extras, ] + $commonAttributes
            );
            $menu->addChild(
                'Add Prospect',
                [
                    'route' => 'prospect_create',
                    'extras' => $this->getIcon('fa fa-user-plus') + $extras, ] + $commonAttributes
            );
        }
    }
}
