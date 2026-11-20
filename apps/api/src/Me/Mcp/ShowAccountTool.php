<?php

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me`, for an AI (ADR 0163).
 */
final readonly class ShowAccountTool
{
    public function __construct(private ToolAccess $access, private MyAccountReader $myAccount, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-mon-compte', description: 'Le profil du membre (nom, coordonnées, photo, rôles) et l\'organisation de son compte (identité, adresse, logo), avec les liens vers ce qu\'il y lit.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myAccount->read($this->access->member()->memberId()));
    }
}
