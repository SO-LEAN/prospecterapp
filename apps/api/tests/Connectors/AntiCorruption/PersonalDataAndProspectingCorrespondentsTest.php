<?php

namespace Tests\App\Connectors\AntiCorruption;

use App\Connectors\AntiCorruption\PersonalDataAndProspectingCorrespondents;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmails;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsOutput;
use Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails\WhichContactsHaveTheseEmailsService;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmails;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsOutput;
use Prospecter\Core\Prospecting\Contract\Service\WhichOrganizationsHaveTheseEmails\WhichOrganizationsHaveTheseEmailsService;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Tests\App\Double\InMemoryLogger;

/**
 * Who of the account the sender of an e-mail is, asked of PersonalData and Prospecting (ADR 0154): an address nobody
 * has is logged by the account only, never the address.
 */
class PersonalDataAndProspectingCorrespondentsTest extends TestCase
{
    private const string ACCOUNT = '0f8b3c1e-5d2a-4c6b-9e7f-1a2b3c4d5e6f';

    private InMemoryLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new InMemoryLogger();
    }

    public function testAnAddressNoContactHasIsLoggedWithoutTheAddress(): void
    {
        $this->correspondents()->contactWithEmail(new AccountId(self::ACCOUNT), new Email('inconnu@exemple.fr'));

        $this->assertSame([['level' => 'info', 'message' => 'The sender of a message is no contact of the account', 'context' => ['account' => self::ACCOUNT]]], $this->logger->records);
    }

    public function testAnAddressNoOrganizationHasIsLoggedWithoutTheAddress(): void
    {
        $this->correspondents()->organizationWithEmail(new AccountId(self::ACCOUNT), new Email('inconnu@exemple.fr'));

        $this->assertSame([['level' => 'info', 'message' => 'The sender of a message is no organization of the account', 'context' => ['account' => self::ACCOUNT]]], $this->logger->records);
    }

    /**
     * @param array<string, string> $contacts      the contact having each address
     * @param array<string, string> $organizations the organization having each address
     */
    private function correspondents(array $contacts = [], array $organizations = []): PersonalDataAndProspectingCorrespondents
    {
        return new PersonalDataAndProspectingCorrespondents(
            new readonly class($contacts) implements WhichContactsHaveTheseEmailsService {
                /** @param array<string, string> $contacts */
                public function __construct(private array $contacts)
                {
                }

                public function __invoke(WhichContactsHaveTheseEmails $query, WhichContactsHaveTheseEmailsOutput $output): void
                {
                    $output->present($this->contacts);
                }
            },
            new readonly class($organizations) implements WhichOrganizationsHaveTheseEmailsService {
                /** @param array<string, string> $organizations */
                public function __construct(private array $organizations)
                {
                }

                public function __invoke(WhichOrganizationsHaveTheseEmails $query, WhichOrganizationsHaveTheseEmailsOutput $output): void
                {
                    $output->present($this->organizations);
                }
            },
            $this->logger,
        );
    }
}
