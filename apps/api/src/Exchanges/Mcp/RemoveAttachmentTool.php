<?php

namespace App\Exchanges\Mcp;

use App\Security\User;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\RemoveAttachment\RemoveAttachment;

/**
 * `DELETE /api/exchanges/{id}/attachments/{attachmentId}`, for an AI (ADR 0163), which asks its member before
 * calling it.
 */
#[Adapts('api_exchanges_remove_attachment')]
final readonly class RemoveAttachmentTool
{
    public function __construct(private ExchangeWrites $writes)
    {
    }

    /**
     * @param string $id           the id of the exchange
     * @param string $attachmentId the id of the file joined to it
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'delete-attachment', description: 'Remove a file joined to an exchange, and forget it; needs the right to manage the personal data. Answers the exchange.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $id, string $attachmentId): array
    {
        return $this->writes->write(
            $id,
            null,
            static fn (null $input, ExchangeDetail $exchange, string $memberId, \DateTimeImmutable $now): RemoveAttachment => new RemoveAttachment($memberId, $exchange->id, $attachmentId, $now),
            User::PERSONAL_DATA,
        );
    }
}
