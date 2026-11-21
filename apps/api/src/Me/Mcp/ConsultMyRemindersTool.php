<?php

namespace App\Me\Mcp;

use App\Me\MyRemindersReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/reminders`, for an AI (ADR 0163).
 */
#[Adapts('api_me_reminders')]
final readonly class ConsultMyRemindersTool
{
    public function __construct(private ToolAccess $access, private MyRemindersReader $myRemindersReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-my-reminders', description: 'The settings of the reminders of the member: when they are told of their exchanges and to-dos.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myRemindersReader->read($this->access->member()->memberId()));
    }
}
