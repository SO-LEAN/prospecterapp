<?php

namespace App\Tags;

use App\Tags\Resource\ContactTagsResource;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The tags of a contact, its own, inherited and automatic, as the API shows them (ADR 0141); a controller calls it
 * after a command, which returns nothing, to answer with the fresh resource.
 */
final readonly class ContactTagsReader
{
    public function __construct(
        private QueryHandler $consultContactTags,
        private TagResourceFactory $tags,
    ) {
    }

    public function read(string $me, string $contactId): ContactTagsResource
    {
        $presenter = new ContactTagsResourcePresenter($this->tags, $contactId);
        ($this->consultContactTags)(new ConsultContactTags($me, [$contactId]), $presenter);

        return $presenter->resource;
    }
}
