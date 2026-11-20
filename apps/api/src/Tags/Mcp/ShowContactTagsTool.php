<?php

namespace App\Tags\Mcp;

use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use App\Tags\ContactTagsReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}/tags`, for an AI (ADR 0163).
 */
final readonly class ShowContactTagsTool
{
    public function __construct(private ToolAccess $access, private ContactTagsReader $contactTagsReader, private ToolResults $results)
    {
    }

    /**
     * @param string $id the id of the contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-tags-contact', description: 'Les tags d\'un contact : les siens, ceux qu\'il hérite de ses organisations, et les automatiques.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->contactTagsReader->read($this->access->member()->memberId(), $id));
    }
}
