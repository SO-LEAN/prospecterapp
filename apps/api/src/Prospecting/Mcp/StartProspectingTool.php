<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\Input\OrganizationContactDetailsInput;
use App\Prospecting\Input\StartProspectingInput;
use App\Prospecting\OrganizationReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Input\AddressInput;
use App\Shared\Input\LegalIdentityInput;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/organizations`, for an AI (ADR 0163).
 */
#[Adapts('api_organizations_start_prospecting')]
final readonly class StartProspectingTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private Identities $identities,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param ?string      $corporateName its corporate name; a corporate name or an e-mail at least
     * @param ?string      $form          its legal form (SAS, SARL…)
     * @param ?string      $type          its type
     * @param ?string      $email         its e-mail
     * @param ?string      $phoneNumber   its phone number, international (+33 3 88 11 22 33) or national in France (03 88 11 22 33)
     * @param ?string      $language      the country of the language spoken with it (FR, GB…)
     * @param list<string> $links         the addresses of its pages on the tools outside (a company on LinkedIn or Welcome to the Jungle, any other site), five at most
     * @param ?string      $website       the address of its site, with or without its scheme
     * @param ?string      $street        the number and the street of its address
     * @param ?string      $postalCode    its postal code
     * @param ?string      $city          its city
     * @param ?string      $country       its country, its code (FR, BE…)
     * @param ?string      $observations  what the members note on it, 255 characters at most
     * @param ?string      $holdingId     the id of its holding, an organization the account prospects
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'start-prospecting', description: 'Start prospecting an organization, owned by the account of the member, located by its address as on the screen. Answers the new organization, and the organizations it may duplicate.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(
        ?string $corporateName = null,
        ?string $form = null,
        ?string $type = null,
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $language = null,
        array $links = [],
        ?string $website = null,
        ?string $street = null,
        ?string $postalCode = null,
        ?string $city = null,
        ?string $country = null,
        ?string $observations = null,
        ?string $holdingId = null,
    ): array {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new StartProspectingInput(
            new LegalIdentityInput($corporateName, $form, $type),
            new OrganizationContactDetailsInput($email, $phoneNumber, $language, $links, $website),
            new AddressInput($street, $postalCode, $city, $country),
            $observations,
            $holdingId,
        ));
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->commandOfAnAI($this->access->member()->memberId(), $id, $this->clock->now()));

        return $this->results->of($this->organizationReader->read($id));
    }
}
