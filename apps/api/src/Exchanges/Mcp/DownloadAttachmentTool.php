<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\AttachmentDownloads;
use App\Exchanges\ExchangeResolver;
use App\Exchanges\ExchangeVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/exchanges/{id}/attachments/{attachmentId}`, for an AI (ADR 0163).
 */
#[Adapts('api_exchanges_download_attachment')]
final readonly class DownloadAttachmentTool
{
    public function __construct(
        private ToolAccess $access,
        private ExchangeResolver $exchanges,
        private AttachmentDownloads $downloads,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id           the id of the exchange
     * @param string $attachmentId the id of the file joined to it
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-attachment-link', description: 'Where to read a file joined to an exchange: its name and a link signed for a minute, with the right to manage the personal data.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id, string $attachmentId): array
    {
        $member = $this->access->member();
        $exchange = $this->exchanges->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ExchangeVoter::ACCESS, $exchange);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->downloads->of($member->memberId(), $exchange->id, $attachmentId));
    }
}
