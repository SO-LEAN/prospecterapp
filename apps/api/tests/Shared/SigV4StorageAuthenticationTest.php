<?php

namespace Tests\App\Shared;

use App\Shared\SigV4StorageAuthentication;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * On AWS, a request to the internal entry of the storage is signed for the function URLs of Lambda.
 */
#[Group('unit')]
class SigV4StorageAuthenticationTest extends TestCase
{
    public function testARequestIsSignedForLambdaWithTheCredentialsOfTheRole(): void
    {
        $authentication = new SigV4StorageAuthentication(['key' => 'AKIDEXAMPLE', 'secret' => 'the-secret', 'token' => 'the-session'], 'eu-west-3');

        $headers = $authentication->headersFor('DELETE', 'https://storage.lambda-url.eu-west-3.on.aws/files/a.png', ['X-Storage-Token' => 'the-token'], '');

        $this->assertMatchesRegularExpression('#^AWS4-HMAC-SHA256 Credential=AKIDEXAMPLE/\d{8}/eu-west-3/lambda/aws4_request, SignedHeaders=[a-z0-9;-]*x-storage-token[a-z0-9;-]*, Signature=[0-9a-f]{64}$#', $headers['Authorization']);
        $this->assertSame('the-session', $headers['X-Amz-Security-Token']);
        $this->assertSame('the-token', $headers['X-Storage-Token']);
    }
}
