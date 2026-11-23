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
     * @param string $id           the exchange's id
     * @param string $attachmentId the id of the file attached to it
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-attachment-link', description: 'Get a link to read a file attached to an exchange. The link is signed for one minute. Needs the right to manage personal data. Returns the file\'s name and the link.', annotations: new ToolAnnotations(readOnlyHint: true))]
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
