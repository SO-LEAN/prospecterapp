<?php

namespace Tests\App\Connectors;

use App\Connectors\Google\GoogleEndpoints;
use App\Connectors\Google\GoogleProviderAccess;
use App\Shared\ServiceUnavailable;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Domain\Exception\AccessNotGranted;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\Grant;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\SharedKernel\Domain\Email;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The OAuth 2 endpoints of Google as the spike #92 saw them answer (ADR 0120), asked with the client of the account of
 * the member (ADR 0125).
 */
class GoogleProviderAccessTest extends TestCase
{
    private const string TOKEN_URL = 'https://oauth2.google.test/token';
    private const string EVERY_SCOPE = 'openid https://www.googleapis.com/auth/calendar.app.created https://www.googleapis.com/auth/userinfo.email';

    public function testACodeIsExchangedWithItsVerifierAndTheSecret(): void
    {
        $google = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertSame(['POST', self::TOKEN_URL], [$method, $url]);
            parse_str($options['body'], $form);
            $this->assertSame(['grant_type' => 'authorization_code', 'code' => 'a-code', 'code_verifier' => 'a-verifier', 'redirect_uri' => 'https://front.test/settings/connectors/google', 'client_id' => 'an-id', 'client_secret' => 'a-secret'], $form);

            return new JsonMockResponse(['refresh_token' => 'a-token', 'scope' => self::EVERY_SCOPE, 'id_token' => self::idToken(['email' => 'michel@exemple.fr', 'hd' => 'exemple.fr'])]);
        });

        $grant = $this->access($google)->exchange(self::client(), 'a-code', 'a-verifier');

        $this->assertEquals(new Grant(new Email('michel@exemple.fr'), new RefreshToken('a-token'), true), $grant);
    }

    public function testAGrantWithoutTheCalendarIsIncomplete(): void
    {
        $google = new MockHttpClient(new JsonMockResponse(['refresh_token' => 'a-token', 'scope' => 'openid https://www.googleapis.com/auth/userinfo.email', 'id_token' => self::idToken(['email' => 'michel@exemple.fr'])]));

        $this->assertFalse($this->access($google)->exchange(self::client(), 'a-code', 'a-verifier')->complete);
    }

    public function testACodeGoogleRefusesGrantsNothing(): void
    {
        $this->expectException(AccessNotGranted::class);

        $this->access(new MockHttpClient(new JsonMockResponse(['error' => 'invalid_grant'], ['http_code' => 400])))->exchange(self::client(), 'a-code', 'a-verifier');
    }

    public function testAnAnswerWithoutRefreshTokenGrantsNothing(): void
    {
        $this->expectException(AccessNotGranted::class);

        $this->access(new MockHttpClient(new JsonMockResponse(['scope' => self::EVERY_SCOPE, 'id_token' => self::idToken(['email' => 'michel@exemple.fr'])])))->exchange(self::client(), 'a-code', 'a-verifier');
    }

    public function testARefreshAcceptedStillGrants(): void
    {
        $this->assertTrue($this->access(new MockHttpClient(new JsonMockResponse(['access_token' => 'an-access'])))->stillGrants(self::client(), new RefreshToken('a-token')));
    }

    public function testARefreshAnsweredInvalidGrantNoLongerGrants(): void
    {
        $refused = new JsonMockResponse(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], ['http_code' => 400]);

        $this->assertFalse($this->access(new MockHttpClient($refused))->stillGrants(self::client(), new RefreshToken('a-token')));
    }

    public function testARefreshGoogleFailsToAnswerLeavesGoogleUnreachable(): void
    {
        $this->expectExceptionObject(ServiceUnavailable::google(new \RuntimeException()));

        $this->access(new MockHttpClient(new MockResponse('', ['http_code' => 503])))->stillGrants(self::client(), new RefreshToken('a-token'));
    }

    public function testARefreshGoogleDoesNotAnswerLeavesGoogleUnreachable(): void
    {
        try {
            $this->access(new MockHttpClient(new MockResponse('', ['error' => 'Google does not answer'])))->stillGrants(self::client(), new RefreshToken('a-token'));
            $this->fail('Google not answering was expected to leave it unreachable');
        } catch (ServiceUnavailable $unreachable) {
            $this->assertSame('googleUnreachable', $unreachable->reason);
        }
    }

    public function testARefreshGoogleRefusesForAnotherReasonIsAFailure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google answered 401 to a refresh');

        $this->access(new MockHttpClient(new JsonMockResponse(['error' => 'invalid_client'], ['http_code' => 401])))->stillGrants(self::client(), new RefreshToken('a-token'));
    }

    #[DoesNotPerformAssertions]
    public function testARevocationFailingIsForgottenAllTheSame(): void
    {
        $this->access(new MockHttpClient(static fn (): never => throw new TransportException('Google does not answer')))->revoke(Provider::Google, new RefreshToken('a-token'));
    }

    private function access(MockHttpClient $google): GoogleProviderAccess
    {
        return new GoogleProviderAccess($google, new GoogleEndpoints('https://front.test/settings/connectors/google', 'https://accounts.google.test/auth', self::TOKEN_URL, 'https://oauth2.google.test/revoke', 'https://www.googleapis.test/calendar/v3'), new NullLogger());
    }

    private static function client(): ClientCredentials
    {
        return new ClientCredentials(Provider::Google, 'an-id', 'a-secret');
    }

    /**
     * @param array<string, mixed> $claims
     */
    private static function idToken(array $claims): string
    {
        $part = static fn (array $content): string => rtrim(strtr(base64_encode(json_encode($content, \JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $part(['alg' => 'RS256']).'.'.$part($claims).'.signature';
    }
}
