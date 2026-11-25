<?php

declare(strict_types=1);

namespace App\Shared\Resource;

/**
 * The routes whose intent a property of a links resource offers: one, or one for each presenter that fills the
 * resource with its own route. The event storming reads them to draw what a view offers, and the API tests check
 * that the link the property carries leads to one of them (docs/architecture/api.md, "Links").
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Offers
{
    /** @var list<string> */
    public array $routes;

    public function __construct(string ...$routes)
    {
        $this->routes = array_values($routes);
    }
}
