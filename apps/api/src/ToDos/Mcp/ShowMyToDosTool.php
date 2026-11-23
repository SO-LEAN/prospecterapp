<?php

declare(strict_types=1);

namespace App\ToDos\Mcp;

use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use App\ToDos\ToDoReader;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/to-dos`, for an AI (ADR 0163).
 */
#[Adapts('api_me_to_dos')]
final readonly class ShowMyToDosTool
{
    public function __construct(private ToolAccess $access, private ToDoReader $toDoReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'list-my-todos', description: 'List the to-dos that the member still has to do. Needs the right to manage personal data. Returns them, the soonest due first.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->toDoReader->mine($this->access->member()->memberId()));
    }
}
