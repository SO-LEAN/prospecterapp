<?php

namespace Tests\App\Api;

use App\Shared\CommandBus;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\ConfigureMailboxNotifications\ConfigureMailboxNotifications;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Prospecter\Core\Connectors\Application\StopMailImport\StopMailImport;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * A member importing their e-mails of Gmail catches up those their mailbox already exchanged with a contact, or with
 * an organization itself: the API answers at once, the import notes them as it notes the others.
 */
#[Group('api')]
class GmailCatchUpApiTest extends ApiTestCase
{
    private const string MAILBOX = 'michel@exemple.fr';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->aMemberOfTheFixtures('client-administrator', Role::User, Role::Admin);
        $this->configureTheTopicOfGmail();
    }

    public function testTheEMailsExchangedWithAContactAreCaughtUpIntoItsTimeline(): void
    {
        $this->login();
        $ada = $this->aContactOfAcme('ada@acme.test');
        $this->importTheMailOf('prospector-user');
        $this->google()->receives('m1', 'Ada Lovelace <ada@acme.test>', 'Votre devis', 'Bonjour Michel.');

        $this->client->request('POST', '/api/contacts/'.$ada.'/gmail-catch-ups');

        $this->assertResponseStatusCodeSame(202);
        $timeline = $this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges');
        $this->assertSame([['email', 'inbound', "Votre devis\n\nBonjour Michel."]], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['direction'], $exchange['text']], $timeline['items']));
    }

    public function testAnAdministratorCatchesUpTheirEMailsToo(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::User, Role::Admin));
        $ada = $this->aContactOfAcme('ada@acme.test');
        $this->importTheMailOf('administrator');
        $this->google()->receives('m1', 'ada@acme.test', 'Votre devis', 'Bonjour.');

        $this->client->request('POST', '/api/contacts/'.$ada.'/gmail-catch-ups');

        $this->assertResponseStatusCodeSame(202);
        $this->assertCount(1, $this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges')['items']);
    }

    public function testAMemberWithoutThePersonalDataCatchesUpNothing(): void
    {
        $this->login();
        $ada = $this->aContactOfAcme('ada@acme.test');
        $this->importTheMailOf('assistant-user');
        $this->signInAnotherMember('assistant-user');

        $this->client->request('POST', '/api/contacts/'.$ada.'/gmail-catch-ups');

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Whether its member imports their e-mails or not, a contact of another account is never told apart from none.
     */
    public function testAContactOfAnotherAccountIsNotFound(): void
    {
        $this->login();
        $ada = $this->aContactOfAcme('ada@acme.test');
        $this->signInAnotherMember($this->aMemberOfAnotherAccount('stranger'));

        $problem = $this->requestJson('POST', '/api/contacts/'.$ada.'/gmail-catch-ups');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $problem['reason']);
    }

    public function testWithoutTheImportActiveTheCatchUpIsRefused(): void
    {
        $this->login();
        $ada = $this->aContactOfAcme('ada@acme.test');
        $this->importTheMailOf('prospector-user');
        self::getContainer()->get(CommandBus::class)->dispatch(new StopMailImport($this->memberIdOf('prospector-user'), 'google', new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));

        $problem = $this->requestJson('POST', '/api/contacts/'.$ada.'/gmail-catch-ups');

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('mailImportNotActive', $problem['reason']);
    }

    public function testAContactWithoutEMailHasNothingToCatchUp(): void
    {
        $this->login();
        $ada = $this->aContactOfAcme(null);
        $this->importTheMailOf('prospector-user');

        $problem = $this->requestJson('POST', '/api/contacts/'.$ada.'/gmail-catch-ups');

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('noAddressToCatchUp', $problem['reason']);
    }

    private function aContactOfAcme(?string $email): string
    {
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => $email], 'organizationIds' => [$acme]]);
        $this->assertResponseStatusCodeSame(201, 'The contact was expected to be collected');

        return $contact['id'];
    }

    private function configureTheTopicOfGmail(): void
    {
        self::getContainer()->get(CommandBus::class)->dispatch(new ConfigureMailboxNotifications($this->memberIdOf('client-administrator'), 'google', 'projects/solean/topics/gmail', 'push@solean.iam.gserviceaccount.com', new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)));
    }

    /**
     * The member connected their Google account importing their mailbox, arranged through the use cases.
     */
    private function importTheMailOf(string $reference): void
    {
        $at = new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS);
        $commands = self::getContainer()->get(CommandBus::class);
        $member = $this->memberIdOf($reference);
        $commands->dispatch(new AskToImportMail($member, 'google', 'a-state', 'a-verifier', $at));
        $this->google()->grantsGmail('a-code', self::MAILBOX, 'a-refresh-token');
        $commands->dispatch(new Connect($member, 'a-state', 'a-code', $at));
    }

    /**
     * The client reboots again for another member, as the other tests of the API sign them in: the double of Google
     * is no longer needed then.
     */
    private function signInAnotherMember(string $member): void
    {
        $this->client->enableReboot();
        $this->login($member);
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }
}
