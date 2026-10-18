<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * A number calling is recognized among the organizations prospected and the contacts, however it is typed
 * (ADR 0072).
 */
#[Group('api')]
class PhoneNumberApiTest extends ApiTestCase
{
    public function testAnOrganizationIsRecognizedByItsNumberHoweverItIsTyped(): void
    {
        $this->login();
        $id = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['phoneNumber' => '03 88 11 22 33']])['id'];

        foreach (['0388112233', '+33388112233', '+33 3 88 11 22 33'] as $calling) {
            $caller = $this->requestJson('GET', '/api/phone-numbers/'.rawurlencode($calling));

            $this->assertResponseIsSuccessful();
            $this->assertSame([$id], array_column($caller['organizations'], 'id'), $calling);
            $this->assertSame('/api/organizations/'.$id, $caller['organizations'][0]['_links']['self']['href']);
            $this->assertSame([], $caller['contacts']);
        }
    }

    public function testAContactIsRecognizedWithTheOrganizationsNamingThem(): void
    {
        $this->login();

        $caller = $this->requestJson('GET', '/api/phone-numbers/'.rawurlencode('+33 3 88 00 00 42'));

        $this->assertSame([], $caller['organizations']);
        $this->assertSame(['Ada'], array_column(array_column($caller['contacts'], 'name'), 'firstName'));
        $this->assertSame('+33 3 88 00 00 42', $caller['contacts'][0]['contactDetails']['phoneNumber']);
        $this->assertSame(['Babbage & Co SAS'], array_column($caller['contacts'][0]['organizations'], 'name'));
    }

    public function testAnUnknownNumberRecognizesNobody(): void
    {
        $this->login();

        $this->assertSame(['organizations' => [], 'contacts' => []], $this->requestJson('GET', '/api/phone-numbers/0123456789'));
    }

    public function testNoValidNumberIsRefused(): void
    {
        $this->login();

        $problem = $this->requestJson('GET', '/api/phone-numbers/12');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('invalidPhoneNumber', $problem['reason']);
        $this->assertSame([['propertyPath' => 'phoneNumber', 'title' => 'Phone number "12" is not valid', 'reason' => 'invalidPhoneNumber', 'context' => ['phoneNumber' => '12']]], $problem['violations']);
    }

    public function testWithoutTheRightToManagePersonalDataACallerIsNotRecognized(): void
    {
        $this->login('assistant-user');

        $problem = $this->requestJson('GET', '/api/phone-numbers/0388000042');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('personalDataManagersOnly', $problem['reason']);
    }

    public function testAnInvalidNumberIsRefusedOnTheContactDetails(): void
    {
        $this->login();
        $id = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$id.'/contact-details', ['email' => null, 'phoneNumber' => 'call me', 'language' => null]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['phoneNumber'], $this->violatedFields($problem));
    }
}
