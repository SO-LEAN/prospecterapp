<?php

declare(strict_types=1);

namespace Tests\App\Local;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Locally, the API and its HTTP API on MiniStack accept the SPA of a stack under its name in `.localhost`, on 443 or
 * on the port of a worktree, and no other origin.
 */
#[Group('api')]
class CorsTest extends WebTestCase
{
    private const string DEPLOYMENT_OF_THE_LOCAL_FUNCTIONS = __DIR__.'/../../../../docker/lambda/deploy.sh';
    private const string PORT_OF_A_WORKTREE = ':8444';

    /**
     * @return iterable<string, array{string}>
     */
    public static function spaOfALocalStack(): iterable
    {
        yield 'main checkout' => ['https://prospecter.localhost'];
        yield 'worktree' => ['https://prospecter.localhost'.self::PORT_OF_A_WORKTREE];
    }

    #[DataProvider('spaOfALocalStack')]
    public function testTheApiAcceptsTheSpaOfALocalStack(string $origin): void
    {
        $this->preflightFrom($origin);

        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', $origin);
    }

    public function testTheApiRefusesAnotherOrigin(): void
    {
        $this->preflightFrom('https://local.prospecter.io');

        $this->assertResponseNotHasHeader('Access-Control-Allow-Origin');
    }

    public function testTheLocalHttpApiAcceptsTheSpaTheApiAccepts(): void
    {
        $allowed = str_replace('$PUBLIC_PORT_SUFFIX', self::PORT_OF_A_WORKTREE, $this->allowedByTheLocalHttpApi());

        $this->assertSame('https://prospecter.localhost'.self::PORT_OF_A_WORKTREE, $allowed);
        $this->assertMatchesRegularExpression('#'.Typed::string($_ENV['CORS_ALLOW_ORIGIN']).'#', $allowed);
    }

    private function preflightFrom(string $origin): void
    {
        $client = static::createClient([], ['HTTP_HOST' => 'api.test.prospecter.io']);
        $client->request('OPTIONS', '/api/user-pool', server: [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
    }

    private function allowedByTheLocalHttpApi(): string
    {
        preg_match('/AllowOrigins=([^,]+),AllowMethods=/', (string) file_get_contents(self::DEPLOYMENT_OF_THE_LOCAL_FUNCTIONS), $match);

        return $match[1] ?? self::fail('The local HTTP API names no origin it allows.');
    }
}
