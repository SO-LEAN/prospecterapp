<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactMailLinksResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\MailImport\ConsultMailCatchUpIntents\ConsultMailCatchUpIntentsOutput;

/**
 * Presents what Connectors offers on a contact: catching up the e-mails exchanged with them. It takes the right to
 * manage personal data, because it imports them (ADR 0081, 0176). One presenter serves one call (ADR 0085).
 */
final class ContactMailPresenter implements ConsultMailCatchUpIntentsOutput
{
    private const string CATCH_UP = 'CatchUpGmail';

    public private(set) ContactMailLinksResource $links;

    public function __construct(private readonly Links $routes, private readonly IntentLinks $intentLinks, private readonly string $contactId)
    {
    }

    public function present(array $possibleIntents): void
    {
        $this->links = new ContactMailLinksResource(
            $this->intentLinks->shows(self::CATCH_UP, $possibleIntents, User::PERSONAL_DATA) ? $this->routes->to('api_contacts_reimport_gmail', ['id' => $this->contactId]) : null,
        );
    }
}
