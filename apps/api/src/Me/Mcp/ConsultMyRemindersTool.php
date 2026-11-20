<?php

namespace App\Me\Mcp;

use App\Me\MyRemindersReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/me/reminders`, for an AI (ADR 0163).
 */
final readonly class ConsultMyRemindersTool
{
    public function __construct(private ToolAccess $access, private MyRemindersReader $myRemindersReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-mes-rappels', description: 'Les réglages des rappels du membre : quand il est prévenu de ses échanges et de ses à-faire.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->results->of($this->myRemindersReader->read($this->access->member()->memberId()));
    }
}
