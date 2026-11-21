<?php

namespace Tests\App\Api;

use App\Shared\CommandBus;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\ConfigureMailboxNotifications\ConfigureMailboxNotifications;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * An AI checks the connections of its member with the services outside and catches up the e-mails of their mailbox
 * of Gmail, as its member does on the screens (ADR 0163); connecting or disconnecting stays on the screen.
 */
#[Group('api')]
class ConnectorToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiChecksTheConnectionsOfItsMember(): void
    {
        $connections = $this->assertTheToolActsAsItsEndpoint('check-my-connections', [], 'POST', '/api/me/connections/checks', null, self::WRITERS);

        $this->assertArrayHasKey('_links', $connections);
    }

    public function testTheAiCatchesUpTheEMailsExchangedWithAContact(): void
    {
        $this->theProspectorImportsTheirMail();
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$this->acme(null)]])['id'];
        $this->google()->receives('m1', 'Ada Lovelace <ada@acme.test>', 'Votre devis', 'Bonjour Michel.');
        $this->assertTheToolIsRefusedAsItsEndpoint('catch-up-contact-emails', ['id' => $ada], 'POST', '/api/contacts/'.$ada.'/gmail-reimports', null, ['assistant-user', 'admin-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('catch-up-contact-emails', ['id' => $ada])['isError'] ?? false);

        $this->assertSame(["Votre devis\n\nBonjour Michel."], array_column($this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges')['items'], 'text'));
    }

    public function testTheAiCatchesUpTheEMailsExchangedWithAnOrganizationItself(): void
    {
        $this->theProspectorImportsTheirMail();
        $acme = $this->acme('contact@acme.test');
        $this->google()->receives('m1', 'ACME <contact@acme.test>', 'Appel d’offres', 'Bonjour Michel.');
        $this->assertTheToolIsRefusedAsItsEndpoint('catch-up-organization-emails', ['id' => $acme], 'POST', '/api/organizations/'.$acme.'/gmail-reimports', null, ['assistant-user', 'admin-user', 'member-user', 'stranger']);
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('catch-up-organization-emails', ['id' => $acme])['isError'] ?? false);

        $this->assertSame(["Appel d’offres\n\nBonjour Michel."], array_map(static fn (array $item): string => $item['exchange']['text'], $this->requestJson('GET', '/api/organizations/'.$acme.'/exchanges')['items']));
    }

    /**
     * The prospector connected their Google account importing their mailbox of Gmail, its notifications configured
     * by the administrator, arranged through the use cases.
     */
    private function theProspectorImportsTheirMail(): void
    {
        $this->keepTheKernel();
        $at = new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS);
        $commands = self::getContainer()->get(CommandBus::class);
        $commands->dispatch(new ConfigureMailboxNotifications($this->memberIdOf('admin-user'), 'google', 'projects/solean/topics/gmail', 'push@solean.iam.gserviceaccount.com', $at));
        $prospector = $this->memberIdOf('prospector-user');
        $commands->dispatch(new AskToImportMail($prospector, 'google', 'a-state', 'a-verifier', $at));
        $this->google()->grantsGmail('a-code', 'michel@exemple.fr', 'a-refresh-token');
        $commands->dispatch(new Connect($prospector, 'a-state', 'a-code', $at));
        $this->login();
    }

    /**
     * ACME, a new organization of the account.
     */
    private function acme(?string $email): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['email' => $email]])['id'];
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }
}
