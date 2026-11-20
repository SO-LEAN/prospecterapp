<?php

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/to-dos`, for an AI (ADR 0163).
 */
final readonly class ShowMyToDosTool
{
    public function __construct(private ToolAccess $access, private ToDoReader $toDoReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-mes-a-faire', description: 'Les à-faire que le membre a encore à faire, les plus proches d\'abord ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDoReader->mine($this->access->member()->memberId()));
    }
}
