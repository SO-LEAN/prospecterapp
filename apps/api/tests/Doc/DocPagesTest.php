<?php

namespace Tests\App\Doc;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The pages of the api a browser opens: its doc, with Swagger UI at a fixed version, and their icon.
 */
#[Group('api')]
class DocPagesTest extends WebTestCase
{
    public function testTheDocLoadsSwaggerUiAtTheInstalledVersionOfTheBundle(): void
    {
        $client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $client->request('GET', '/api/doc');

        $this->assertResponseIsSuccessful();
        $version = InstalledVersions::getPrettyVersion('nelmio/api-doc-bundle');
        $this->assertStringContainsString("https://cdn.jsdelivr.net/gh/nelmio/NelmioApiDocBundle@{$version}/public/swagger-ui/swagger-ui-bundle.js", (string) $client->getResponse()->getContent());
        $this->assertStringNotContainsString('NelmioApiDocBundle/public', (string) $client->getResponse()->getContent());
    }

    public function testThePagesHaveAnIcon(): void
    {
        $client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $client->request('GET', '/favicon.ico');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<title>Prospecter</title>', (string) $client->getResponse()->getContent());
    }
}
