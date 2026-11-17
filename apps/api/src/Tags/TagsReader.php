<?php

namespace App\Tags;

use App\Shared\Links;
use App\Tags\Resource\TagsResource;
use Prospecter\Core\Campaigns\Application\ConsultTags\ConsultTags;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The tags of an organization or of a contact, as the API shows them; a controller calls it after a command, which
 * returns nothing, to answer with the fresh resource.
 */
final readonly class TagsReader
{
    public function __construct(
        private QueryHandler $consultTags,
        private Links $links,
    ) {
    }

    public function read(string $me, TagRoutes $holder, string $holderId): TagsResource
    {
        $presenter = new TagsResourcePresenter($this->links, $holder, $holderId);
        ($this->consultTags)(new ConsultTags($me, $holder->value, $holderId), $presenter);

        return $presenter->resource;
    }
}
