<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * My reminders follow my account, whatever the device: ten minutes before by default, mine only.
 */
#[Group('api')]
class RemindersApiTest extends ApiTestCase
{
    #[DataProvider('members')]
    public function testEveryMemberIsRemindedTenMinutesBeforeByDefault(string $member): void
    {
        $this->login($member);

        $reminders = $this->requestJson('GET', '/api/me/reminders');

        $this->assertResponseIsSuccessful();
        $this->assertSame([true, 10], [$reminders['enabled'], $reminders['minutesBefore']]);
        $this->assertSame(['href' => '/api/me/reminders', 'method' => 'PATCH'], $reminders['_links']['changeMyReminders']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function members(): iterable
    {
        yield 'a prospector with the personal data' => ['prospector-user'];
        yield 'a prospector without the personal data' => ['assistant-user'];
    }

    #[DataProvider('members')]
    public function testEveryMemberChangesTheirRemindersAndFindsThemAgain(string $member): void
    {
        $this->login($member);

        $changed = $this->requestJson('PATCH', '/api/me/reminders', ['enabled' => false, 'minutesBefore' => 30]);
        $this->assertResponseIsSuccessful();
        $this->assertSame([false, 30], [$changed['enabled'], $changed['minutesBefore']]);

        $read = $this->requestJson('GET', '/api/me/reminders');
        $this->assertSame([false, 30], [$read['enabled'], $read['minutesBefore']]);
    }

    public function testMyRemindersAreMineOnly(): void
    {
        $this->login();
        $this->requestJson('PATCH', '/api/me/reminders', ['enabled' => true, 'minutesBefore' => 60]);

        $this->login('assistant-user');
        $theirs = $this->requestJson('GET', '/api/me/reminders');

        $this->assertSame(10, $theirs['minutesBefore']);
    }

    public function testADelayNotOfferedIsRefused(): void
    {
        $this->login();

        $problem = $this->requestJson('PATCH', '/api/me/reminders', ['enabled' => true, 'minutesBefore' => 7]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['minutesBefore'], $this->violatedFields($problem));
        $this->assertSame('reminderDelayNotOffered', $problem['reason']);
    }

    public function testBothPartsAreRequired(): void
    {
        $this->login();

        $problem = $this->requestJson('PATCH', '/api/me/reminders', ['minutesBefore' => 30]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['enabled'], $this->violatedFields($problem));
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    #[DataProvider('gestures')]
    public function testAnonymousCallersAreRejected(string $method, ?array $payload): void
    {
        $this->requestJson($method, '/api/me/reminders', $payload);

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>|null}>
     */
    public static function gestures(): iterable
    {
        yield 'consulting' => ['GET', null];
        yield 'changing' => ['PATCH', ['enabled' => true, 'minutesBefore' => 30]];
    }
}
