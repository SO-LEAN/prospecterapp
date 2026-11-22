<?php

declare(strict_types=1);

namespace App\Me\Mcp;

use App\PersonalData\Input\PersonContactDetailsInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;

/**
 * `PATCH /api/me/contact-details`, for an AI (ADR 0163).
 */
#[Adapts('api_me_change_contact_details')]
final readonly class ChangeMyContactDetailsTool
{
    public function __construct(private MyProfileWrites $writes)
    {
    }

    /**
     * @param ?string      $email       their e-mail
     * @param ?string      $phoneNumber their phone number, international (+33 3 88 11 22 33) or national in France (03 88 11 22 33)
     * @param ?string      $language    the country of the language they speak (FR, GB…)
     * @param list<string> $links       the addresses of their pages on the tools outside (their profile on LinkedIn or Malt, any other site), five at most
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-my-contact-details', description: 'Change the e-mail, phone number, language and links of the member in their profile; what is left out is removed. Answers their account.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(?string $email = null, ?string $phoneNumber = null, ?string $language = null, array $links = []): array
    {
        return $this->writes->write(
            new PersonContactDetailsInput($email, $phoneNumber, $language, $links),
            static fn (PersonContactDetailsInput $input, string $memberId, string $personId, \DateTimeImmutable $now): ChangePersonContactDetails => new ChangePersonContactDetails(readerId: $memberId, id: $personId, email: $input->email, phoneNumber: $input->phoneNumber, language: $input->language, links: $input->links, executedAt: $now),
        );
    }
}
