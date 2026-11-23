<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Shared\Typed;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * A controller shows its dependencies in its constructor (ADR 0044). Extending `AbstractController` would hand it a
 * hidden container instead.
 */
final class ControllersExtendNothingTest extends KernelTestCase
{
    private const string OF_THE_API = 'App\\';

    public function testEveryControllerOfTheApiIsAFinalReadonlyClassExtendingNothing(): void
    {
        $controllers = array_unique(array_filter(array_map(self::controllerOf(...), $this->routes())));

        $this->assertNotEmpty($controllers);
        $this->assertSame([], array_values(array_filter($controllers, static fn (string $controller): bool => !self::extendsNothing(new \ReflectionClass($controller)))), 'Controllers that are not final readonly classes extending nothing');
    }

    /**
     * @return list<Route>
     */
    private function routes(): array
    {
        return array_values(Typed::object(self::getContainer()->get(RouterInterface::class), RouterInterface::class)->getRouteCollection()->all());
    }

    /**
     * @return class-string|null
     */
    private static function controllerOf(Route $route): ?string
    {
        $controller = explode('::', Typed::string($route->getDefault('_controller')))[0];

        return str_starts_with($controller, self::OF_THE_API) && class_exists($controller) ? $controller : null;
    }

    /**
     * @param \ReflectionClass<object> $controller
     */
    private static function extendsNothing(\ReflectionClass $controller): bool
    {
        return $controller->isFinal() && $controller->isReadOnly() && false === $controller->getParentClass();
    }
}
