<?php

namespace App\Prospecting\Mcp;

use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ImageAddressInput;
use App\Shared\Mcp\ImagesOnTheWeb;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ChangeLogo\ChangeLogo;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;

/**
 * `POST /api/organizations/{id}/logo`, for an AI (ADR 0163): the image given by its address on the web, which
 * Prospecter downloads, in place of the file the screen sends (ADR 0168).
 */
#[Adapts('api_organizations_change_logo')]
final readonly class ChangeLogoTool
{
    public function __construct(private OrganizationWrites $writes, private ImagesOnTheWeb $images)
    {
    }

    /**
     * @param string $id  the id of the organization
     * @param string $url the address on the web of its logo, a JPEG, PNG or WebP image of 2 MB at most, downloaded by Prospecter without following a redirect
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'changer-logo-organisation', description: 'Give an organization a new logo by the address of its image on the web (its site, its page on LinkedIn…), which Prospecter downloads and keeps. Answers the organization.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true))]
    public function __invoke(string $id, string $url): array
    {
        return $this->writes->write(
            $id,
            new ImageAddressInput($url),
            fn (ImageAddressInput $input, OrganizationSheet $organization, string $memberId, \DateTimeImmutable $now): ChangeLogo => new ChangeLogo($memberId, $organization->id, $this->images->download($input), $now),
        );
    }
}
