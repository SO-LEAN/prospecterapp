<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/contacts/{id}`, for an AI (ADR 0163).
 */
final readonly class ShowContactTool
{
    public function __construct(private ToolAccess $access, private ContactReader $contactReader, private ToolResults $results)
    {
    }

    /**
     * @param string $id the id of the contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-contact', description: 'Un contact du compte : son nom, sa civilité, sa fonction, ses coordonnées (en clair avec le droit de gérer les données personnelles, masquées sinon), et les liens vers ses échanges, ses à-faire, ses tags.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->contactReader->read($this->access->member()->memberId(), $id));
    }
}
