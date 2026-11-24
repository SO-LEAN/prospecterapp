<?php

declare(strict_types=1);

namespace App\Tags;

use App\Tags\Resource\ContactTagsResource;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * A contact's own, inherited and automatic tags, as the API shows them (ADR 0141). A controller calls it after a
 * command, which returns nothing, to answer with the fresh resource.
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
        $this->consultContactTags->__invoke(new ConsultContactTags($me, [$contactId]), $presenter);

        return $presenter->resource;
    }
}
