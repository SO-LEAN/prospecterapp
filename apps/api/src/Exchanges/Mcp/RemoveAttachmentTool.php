<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\RemoveAttachment\RemoveAttachment;

/**
 * `DELETE /api/exchanges/{id}/attachments/{attachmentId}`, for an AI (ADR 0163). The AI asks its member before it
 * calls this tool.
 */
#[Adapts('api_exchanges_remove_attachment')]
final readonly class RemoveAttachmentTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string $id           the exchange's id
     * @param string $attachmentId the id of the file attached to it
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'delete-attachment', description: 'Remove a file attached to an exchange, and forget it. Needs the right to manage personal data. Returns the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $attachmentId): array
    {
        return $this->writes->change(
            $id,
            static fn (ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): RemoveAttachment => new RemoveAttachment(prospectorId: $memberId, exchangeId: $exchange->id, id: $attachmentId, executedAt: $now),
            User::PERSONAL_DATA,
        );
    }
}
