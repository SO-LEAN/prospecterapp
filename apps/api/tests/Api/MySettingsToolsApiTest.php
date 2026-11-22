<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\TheWeb;

/**
 * An AI changes the reminders and the profile of its member, as its member does on the screens (ADR 0163): every
 * member, each their own.
 */
#[Group('api')]
class MySettingsToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const string PICTURE_ON_THE_WEB = TheWeb::HOST.'/michel.png';

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiChangesTheRemindersOfItsMember(): void
    {
        $reminders = ['enabled' => true, 'minutesBefore' => 30];

        $changed = $this->assertTheToolActsAsItsEndpoint('update-my-reminders', $reminders, 'PATCH', '/api/me/reminders', $reminders, self::WRITERS);

        $this->assertSame([true, 30], [$changed['enabled'], $changed['minutesBefore']]);
    }

    public function testTheAiRenamesItsMember(): void
    {
        $name = ['firstName' => 'Michel', 'lastName' => 'Durand'];

        $account = $this->assertTheToolActsAsItsEndpoint('set-my-name', $name, 'PATCH', '/api/me/name', $name, self::WRITERS);

        $this->assertSame(['Michel', 'Durand'], [$account['name']['firstName'], $account['name']['lastName']]);
    }

    public function testTheAiChangesTheContactDetailsOfItsMember(): void
    {
        $details = ['email' => 'michel@solean.test', 'phoneNumber' => '+33612345678', 'language' => 'FR', 'links' => ['https://www.linkedin.com/in/michel-durand']];

        $account = $this->assertTheToolActsAsItsEndpoint('update-my-contact-details', $details, 'PATCH', '/api/me/contact-details', $details, self::WRITERS);

        $this->assertSame('michel@solean.test', $account['contactDetails']['email']);
    }

    public function testTheAiGivesThePictureOfItsMemberByItsAddressOnTheWeb(): void
    {
        $this->keepTheKernel();
        self::getContainer()->get(TheWeb::class)->publish(self::PICTURE_ON_THE_WEB, (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));

        $account = $this->assertTheToolActsAsItsEndpoint('set-my-picture', ['url' => self::PICTURE_ON_THE_WEB], 'POST', '/api/me/picture', null, self::WRITERS);

        $this->assertStringEndsWith('.png', $account['picture']['url']);
    }
}
