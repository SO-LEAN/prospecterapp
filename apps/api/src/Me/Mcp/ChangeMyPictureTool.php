<?php

namespace App\Me\Mcp;

use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ImagesOnTheWeb;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\WebAddressInput;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\ChangePersonPicture\ChangePersonPicture;
use Symfony\Component\HttpFoundation\File\File;

/**
 * `POST /api/me/picture`, for an AI (ADR 0163), the image given by its address on the web (ADR 0168).
 */
#[Adapts('api_me_change_picture')]
final readonly class ChangeMyPictureTool
{
    public function __construct(private MyProfileWrites $writes, private ToolInputs $inputs, private ImagesOnTheWeb $images)
    {
    }

    /**
     * @param string $url the address on the web of their picture, a JPEG, PNG or WebP image of 2 MB at most, downloaded by Prospecter without following a redirect
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-my-picture', description: 'Give the member a new picture in their profile by the address of its image on the web, which Prospecter downloads and keeps. Answers their account.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true))]
    public function __invoke(string $url): array
    {
        $this->writes->writable();

        return $this->images->whileDownloaded(
            $this->inputs->valid(new WebAddressInput($url)),
            fn (File $picture): array => $this->writes->write(
                null,
                static fn (null $input, string $memberId, string $personId, \DateTimeImmutable $now): ChangePersonPicture => new ChangePersonPicture(readerId: $memberId, id: $personId, picture: $picture, executedAt: $now),
            ),
        );
    }
}
