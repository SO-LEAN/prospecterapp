<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ImagesOnTheWeb;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\WebAddressInput;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ChangeLogo\ChangeLogo;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\HttpFoundation\File\File;

/**
 * `POST /api/organizations/{id}/logo`, for an AI (ADR 0163). The AI gives the image's web address, and
 * Prospecter downloads the image. The screen sends the file instead (ADR 0168).
 */
#[Adapts('api_organizations_change_logo')]
final readonly class ChangeLogoTool
{
    public function __construct(private OrganizationWrites $writes, private ToolInputs $inputs, private ImagesOnTheWeb $images)
    {
    }

    /**
     * @param string $id  the id of the organization
     * @param string $url its logo's web address: a JPEG, PNG or WebP image of 2 MB at most. Prospecter downloads it and follows no redirect
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-organization-logo', description: 'Give an organization a new logo from the image\'s web address (on its website, its LinkedIn page…). Prospecter downloads and keeps the image. Returns the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true))]
    public function __invoke(string $id, string $url): array
    {
        $this->writes->writable($id);

        return $this->images->whileDownloaded(
            $this->inputs->valid(new WebAddressInput($url)),
            fn (File $logo): array => $this->writes->change(
                $id,
                static fn (OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): ChangeLogo => new ChangeLogo(prospectorId: $memberId, id: $organization->id, logo: $logo, executedAt: $now),
            ),
        );
    }
}
