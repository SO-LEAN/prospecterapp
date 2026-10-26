<?php

namespace Tests\App\Local;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Locally, the profiler keeps the history of the requests out of the cache, and the front reads the profile of a
 * call; elsewhere the CORS exposes the location only.
 */
#[Group('api')]
class ProfilerTest extends WebTestCase
{
    private const string DEPLOYMENT_OF_THE_LOCAL_FUNCTIONS = __DIR__.'/../../../../docker/lambda/deploy.sh';

    public function testOutOfDevTheCorsExposesTheLocationOnly(): void
    {
        $client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $client->request('GET', '/api/user-pool', server: ['HTTP_ORIGIN' => 'https://local.prospecter.io']);

        $this->assertResponseHeaderSame('Access-Control-Expose-Headers', 'location');
    }

    public function testInDevTheProfilesAreKeptOutOfTheCache(): void
    {
        $dsn = $this->devConfiguration('web_profiler')['framework']['profiler']['dsn'];

        $this->assertSame('file:%kernel.project_dir%/var/profiler', $dsn);
    }

    public function testInDevTheCorsOfTheApiAndOfItsLocalHttpApiExposeTheDebugTokens(): void
    {
        $exposed = $this->devConfiguration('nelmio_cors')['nelmio_cors']['defaults']['expose_headers'];

        $this->assertSame(['Location', 'X-Debug-Token', 'X-Debug-Token-Link'], $exposed);
        $this->assertSame(array_map(strtolower(...), $exposed), $this->exposedByTheLocalHttpApi());
    }

    /**
     * @return array<string, mixed>
     */
    private function devConfiguration(string $package): array
    {
        return Yaml::parseFile(self::getContainer()->getParameter('kernel.project_dir')."/config/packages/{$package}.yaml")['when@dev'];
    }

    /**
     * @return list<string>
     */
    private function exposedByTheLocalHttpApi(): array
    {
        preg_match('/ExposeHeaders=([a-z,-]+?),MaxAge=/', (string) file_get_contents(self::DEPLOYMENT_OF_THE_LOCAL_FUNCTIONS), $match);

        return explode(',', $match[1]);
    }
}
