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
    private const string API_NAMESPACE = 'App\\';

    private const string ITS_OWN_JSON = 'new JsonResponse(';

    public function testEveryControllerOfTheApiIsAFinalReadonlyClassExtendingNothing(): void
    {
        $controllers = $this->controllers();

        $this->assertNotEmpty($controllers);
        $this->assertSame([], array_values(array_filter($controllers, static fn (string $controller): bool => !self::extendsNothing(new \ReflectionClass($controller)))), 'Controllers that are not final readonly classes extending nothing');
    }

    public function testNoControllerWritesItsJsonItself(): void
    {
        $this->assertSame([], array_values(array_filter($this->controllers(), static fn (string $controller): bool => self::writesItsJsonItself(new \ReflectionClass($controller)))), 'Controllers that write their JSON without Shared\\JsonResponses');
    }

    /**
     * @return list<class-string>
     */
    private function controllers(): array
    {
        return array_values(array_unique(array_filter(array_map(self::controllerOf(...), $this->routes()))));
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

        return str_starts_with($controller, self::API_NAMESPACE) && class_exists($controller) ? $controller : null;
    }

    /**
     * @param \ReflectionClass<object> $controller
     */
    private static function extendsNothing(\ReflectionClass $controller): bool
    {
        return $controller->isFinal() && $controller->isReadOnly() && false === $controller->getParentClass();
    }

    /**
     * @param \ReflectionClass<object> $controller
     */
    private static function writesItsJsonItself(\ReflectionClass $controller): bool
    {
        return str_contains(Typed::string(file_get_contents(Typed::string($controller->getFileName()))), self::ITS_OWN_JSON);
    }
}
