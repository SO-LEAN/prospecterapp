<?php

namespace App\Shared;

use App\Shared\Resource\LinkResource;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Builds the link of a route, with the method that route accepts.
 */
final readonly class Links
{
    public function __construct(private RouterInterface $router)
    {
    }

    /**
     * @param array<string, string> $parameters
     */
    public function to(string $routeName, array $parameters = []): LinkResource
    {
        $route = $this->router->getRouteCollection()->get($routeName) ?? throw new \LogicException(sprintf('No route "%s"', $routeName));

        return new LinkResource($this->router->generate($routeName, $parameters, UrlGeneratorInterface::ABSOLUTE_PATH), $route->getMethods()[0] ?? 'GET');
    }
}
