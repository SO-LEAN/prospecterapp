<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * A contact is read with its revision. A screen or an AI sends it back with its change: if another member changed
 * the contact since, the change is refused instead of erasing theirs (ADR 0187).
 */
#[Group('api')]
class ContactRevisionApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private string $ada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Acme']]), 'id');
        $this->ada = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$acme]]), 'id');
    }

    public function testAContactIsReadWithItsRevision(): void
    {
        $contact = $this->requestJson('GET', '/api/contacts/'.$this->ada);

        $this->assertSame(1, $contact['revision']);
    }

    public function testAContactChangedSinceItWasReadIsNotRenamedAndTheOtherChangeIsKept(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/contacts/'.$this->ada.'/name?revision='.$read, ['firstName' => 'Grace']);

        $this->assertChangedMeanwhile($problem);
        $contact = $this->requestJson('GET', '/api/contacts/'.$this->ada);
        $this->assertSame('Ada', self::valueAt($contact, 'name', 'firstName'));
        $this->assertSame('CTO', $contact['jobTitle']);
    }

    public function testTheTitleOfAContactChangedSinceItWasReadIsNotChanged(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/contacts/'.$this->ada.'/title?revision='.$read, ['title' => 'ms']);

        $this->assertChangedMeanwhile($problem);
        $this->assertNotSame('ms', $this->requestJson('GET', '/api/contacts/'.$this->ada)['title']);
    }

    public function testTheJobTitleOfAContactChangedSinceItWasReadIsNotChanged(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/contacts/'.$this->ada.'/job-title?revision='.$read, ['jobTitle' => 'CEO']);

        $this->assertChangedMeanwhile($problem);
        $this->assertSame('CTO', $this->requestJson('GET', '/api/contacts/'.$this->ada)['jobTitle']);
    }

    public function testTheContactDetailsOfAContactChangedSinceItWasReadAreNotChanged(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/contacts/'.$this->ada.'/contact-details?revision='.$read, ['phoneNumber' => '03 88 11 22 33']);

        $this->assertChangedMeanwhile($problem);
        $this->assertNull(self::valueAt($this->requestJson('GET', '/api/contacts/'.$this->ada), 'contactDetails', 'phoneNumber'));
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('toolsChangingAContact')]
    public function testEachToolChangingAContactChangedSinceTheAIReadItIsRefused(string $tool, array $arguments): void
    {
        $read = $this->changedSinceRead();

        $refusal = $this->callWithoutHandshake($tool, ['id' => $this->ada, ...$arguments, 'revision' => $read]);

        $this->assertSame('changedMeanwhile', $this->answerOf($refusal)['reason'] ?? null);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function toolsChangingAContact(): iterable
    {
        yield 'name' => ['set-contact-name', ['firstName' => 'Grace']];
        yield 'title' => ['set-contact-title', ['title' => 'ms']];
        yield 'job title' => ['set-contact-job-title', ['jobTitle' => 'CEO']];
        yield 'details' => ['update-contact-details', ['phoneNumber' => '03 88 11 22 33']];
    }

    /**
     * @return int the revision read before another member changed the contact
     */
    private function changedSinceRead(): int
    {
        $read = self::intAt($this->requestJson('GET', '/api/contacts/'.$this->ada), 'revision');
        $this->requestJson('PATCH', '/api/contacts/'.$this->ada.'/job-title', ['jobTitle' => 'CTO']);

        return $read;
    }

    /**
     * @param array<string, mixed> $problem
     */
    private function assertChangedMeanwhile(array $problem): void
    {
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
    }
}
