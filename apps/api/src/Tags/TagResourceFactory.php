<?php

declare(strict_types=1);

namespace App\Tags;

use App\Shared\Links;
use App\Tags\Resource\TagLinksResource;
use App\Tags\Resource\TagResource;
use App\Tags\Resource\TagsLinksResource;

/**
 * The tags put by hand on an organization or a contact, as the API shows them. Each tag is taken off by its name
 * without its `#`. The factory also gives the links of the holder's tags.
 */
final readonly class TagResourceFactory
{
    public function __construct(private Links $links)
    {
    }

    public function putByHand(TagRoutes $routes, string $holderId, string $name): TagResource
    {
        return new TagResource($name, new TagLinksResource($this->links->to($routes->takeOff(), ['id' => $holderId, 'tag' => ltrim($name, '#')])));
    }

    public function linksOf(TagRoutes $routes, string $holderId): TagsLinksResource
    {
        return new TagsLinksResource($this->links->to($routes->show(), ['id' => $holderId]), $this->links->to($routes->tag(), ['id' => $holderId]));
    }
}
