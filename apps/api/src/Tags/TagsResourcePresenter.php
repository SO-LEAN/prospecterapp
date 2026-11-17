<?php

namespace App\Tags;

use App\Shared\Links;
use App\Tags\Resource\TagLinksResource;
use App\Tags\Resource\TagResource;
use App\Tags\Resource\TagsLinksResource;
use App\Tags\Resource\TagsResource;
use Prospecter\Core\Campaigns\Application\ConsultTags\ConsultTagsOutput;

/**
 * Presents the tags of an organization or of a contact as the API shows them, an object of the call (ADR 0085): each
 * taken off by its name without its mark.
 */
final class TagsResourcePresenter implements ConsultTagsOutput
{
    public private(set) TagsResource $resource;

    public function __construct(private readonly Links $links, private readonly TagRoutes $routes, private readonly string $holderId)
    {
    }

    public function present(array $tags): void
    {
        $this->resource = new TagsResource(
            array_map($this->tag(...), $tags),
            new TagsLinksResource(
                $this->links->to($this->routes->show(), ['id' => $this->holderId]),
                $this->links->to($this->routes->tag(), ['id' => $this->holderId]),
            ),
        );
    }

    private function tag(string $name): TagResource
    {
        return new TagResource($name, new TagLinksResource($this->links->to($this->routes->takeOff(), ['id' => $this->holderId, 'tag' => ltrim($name, '#')])));
    }
}
