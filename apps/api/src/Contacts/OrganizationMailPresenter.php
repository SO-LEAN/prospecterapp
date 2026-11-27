<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\OrganizationMailLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\MailImport\ConsultMailCatchUpIntents\ConsultMailCatchUpIntentsOutput;

/**
 * Presents what Connectors offers on an organization: catching up the e-mails exchanged with the organization itself.
 * It takes the right to manage personal data, because it imports them (ADR 0081, 0176). One presenter serves one call
 * (ADR 0085).
 */
final class OrganizationMailPresenter implements ConsultMailCatchUpIntentsOutput
{
    private const string CATCH_UP = 'CatchUpGmail';

    public private(set) OrganizationMailLinksResource $links;

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks, private readonly string $organizationId)
    {
    }

    public function present(array $possibleIntents): void
    {
        $this->links = new OrganizationMailLinksResource(
            $this->intentLinks->shows(self::CATCH_UP, $possibleIntents, User::PERSONAL_DATA) ? $this->routes->to('api_organizations_reimport_gmail', ['id' => $this->organizationId]) : null,
        );
    }
}
