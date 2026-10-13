<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

#[Group('api')]
class OrganizationApiTest extends ApiTestCase
{
    private const array ACME = [
        'corporateName' => 'ACME',
        'form' => 'SAS',
        'email' => 'contact@acme.test',
        'street' => '1 rue de la Paix',
        'postalCode' => '75002',
        'city' => 'Paris',
        'country' => 'FR',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testCreateThenRead(): void
    {
        $created = $this->requestJson('POST', '/api/organizations', self::ACME);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/organizations/'.$created['id']);
        $this->assertSame('ACME', $created['corporateName']);
        $this->assertSame(['street' => '1 rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'country' => 'FR'], $created['address']);
        $this->assertNull($created['logo']);

        $this->assertSame($created, $this->requestJson('GET', '/api/organizations/'.$created['id']));
    }

    public function testListIsPaginatedAndSearchable(): void
    {
        $this->requestJson('POST', '/api/organizations', self::ACME);
        $this->requestJson('POST', '/api/organizations', ['corporateName' => 'Globex']);

        $page = $this->requestJson('GET', '/api/organizations?q=acm');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $page['totalItems']);
        $this->assertSame(1, $page['page']);
        $this->assertSame('ACME SAS', $page['items'][0]['name'], 'the name joins the corporate name and the legal form');
        $this->assertSame('Paris', $page['items'][0]['city']);
    }

    public function testReplaceClearsLeftOutFields(): void
    {
        $created = $this->requestJson('POST', '/api/organizations', self::ACME);

        $updated = $this->requestJson('PUT', '/api/organizations/'.$created['id'], ['corporateName' => 'ACME Corp']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('ACME Corp', $updated['corporateName']);
        $this->assertNull($updated['email']);
        $this->assertNull($updated['address']);
    }

    public function testUploadAndRemoveLogo(): void
    {
        $created = $this->requestJson('POST', '/api/organizations', self::ACME);

        $withLogo = $this->uploadImage('/api/organizations/'.$created['id'].'/logo');

        $this->assertResponseIsSuccessful();
        $this->assertStringEndsWith('.png', $withLogo['logo']['url']);
        $this->assertNotSame($withLogo['logo']['url'], $withLogo['logo']['thumbnailUrl']);
        $this->assertSame('ACME', $withLogo['corporateName'], 'uploading a logo keeps the other fields');

        $storedFile = $this->storagePath($withLogo['logo']['url']);
        $this->assertFileExists($storedFile);

        $withoutLogo = $this->requestJson('DELETE', '/api/organizations/'.$created['id'].'/logo');

        $this->assertResponseIsSuccessful();
        $this->assertNull($withoutLogo['logo']);
        $this->assertFileDoesNotExist($storedFile);
    }

    public function testInvalidPayloadIsRejectedWithViolations(): void
    {
        $problem = $this->requestJson('POST', '/api/organizations', ['corporateName' => 'ACME', 'email' => 'not an email', 'country' => 'XX']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email', 'country'], $this->violatedFields($problem));
    }

    public function testBusinessRuleViolationIsRejected(): void
    {
        $problem = $this->requestJson('POST', '/api/organizations', ['city' => 'Paris']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertSame([''], $this->violatedFields($problem));
    }

    public function testUnknownOrganizationIsNotFound(): void
    {
        $this->requestJson('GET', '/api/organizations/999');

        $this->assertResponseStatusCodeSame(404);
    }

    private function storagePath(string $url): string
    {
        return str_replace($_ENV['APP_STORAGE_URL'], self::getContainer()->getParameter('kernel.project_dir').'/public/static/storage/test', $url);
    }
}
