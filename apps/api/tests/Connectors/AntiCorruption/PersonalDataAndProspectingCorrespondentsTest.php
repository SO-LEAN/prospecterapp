<?php

declare(strict_types=1);

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

        $this->assertSame([['level' => 'info', 'message' => 'The message\'s sender is not a contact of the account', 'context' => ['account' => self::ACCOUNT]]], $this->logger->records);
    }

    public function testAnAddressNoOrganizationHasIsLoggedWithoutTheAddress(): void
    {
        $this->correspondents()->organizationWithEmail(new AccountId(self::ACCOUNT), new Email('inconnu@exemple.fr'));

        $this->assertSame([['level' => 'info', 'message' => 'The message\'s sender is not an organization of the account', 'context' => ['account' => self::ACCOUNT]]], $this->logger->records);
    }

    /**
     * Nobody of the account has any address.
     */
    private function correspondents(): PersonalDataAndProspectingCorrespondents
    {
        return new PersonalDataAndProspectingCorrespondents(
            new class implements WhichContactsHaveTheseEmailsService {
                public function __invoke(WhichContactsHaveTheseEmails $query, WhichContactsHaveTheseEmailsOutput $output): void
                {
                    $output->present([]);
                }
            },
            new class implements WhichOrganizationsHaveTheseEmailsService {
                public function __invoke(WhichOrganizationsHaveTheseEmails $query, WhichOrganizationsHaveTheseEmailsOutput $output): void
                {
                    $output->present([]);
                }
            },
            $this->logger,
        );
    }
}
