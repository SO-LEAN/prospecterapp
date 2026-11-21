<?php

namespace Tests\App\Base;

use App\Demo\DemoCredentials;
use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

/**
 * The user pool every API test starts from (ADR 0052): recreated once in each process of the tests, with the members of
 * the demonstration. Each test after gets them back as they were: the users and the clients a test added deleted, the
 * login email and the password of each member of the demonstration given back, the pool kept. A sign-out everywhere
 * needs nothing: MiniStack revokes only the tokens issued before its second.
 */
final class MembersOfTheDemonstration
{
    private const int LIST_SIZE = 60;

    private static bool $made = false;

    public function __construct(
        private readonly UserPool $pool,
        private readonly DemoCredentials $demoCredentials,
        private readonly Credentials $credentials,
        private readonly CognitoIdentityProviderClient $cognito,
    ) {
    }

    public function restore(): void
    {
        if (!self::$made) {
            $this->pool->recreate();
            $this->demoCredentials->register();
            self::$made = true;

            return;
        }

        $this->deleteTheClientsAdded();
        $loginEmails = $this->deleteTheUsersAdded();

        foreach (DemoCredentials::LOGIN_EMAILS as $memberId => $loginEmail) {
            $this->giveBack($memberId, $loginEmail, $loginEmails[$memberId] ?? null);
        }
    }

    private function giveBack(string $memberId, string $loginEmail, ?string $current): void
    {
        if (null === $current) {
            $this->credentials->register($memberId, $loginEmail, DemoCredentials::PASSWORD);

            return;
        }

        if ($loginEmail !== $current) {
            $this->credentials->changeLoginEmail($memberId, $loginEmail);
        }

        $this->credentials->changePassword($memberId, DemoCredentials::PASSWORD);
    }

    /**
     * @return array<string, string> the login email of each member of the demonstration left, by member id
     */
    private function deleteTheUsersAdded(): array
    {
        $loginEmails = [];

        foreach ($this->cognito->getPaginator('ListUsers', ['UserPoolId' => $this->pool->id(), 'Limit' => self::LIST_SIZE]) as $page) {
            foreach ($page['Users'] ?? [] as $user) {
                if (!array_key_exists($user['Username'], DemoCredentials::LOGIN_EMAILS)) {
                    $this->cognito->adminDeleteUser(['UserPoolId' => $this->pool->id(), 'Username' => $user['Username']]);

                    continue;
                }

                $loginEmails[$user['Username']] = (string) array_column($user['Attributes'] ?? [], 'Value', 'Name')['email'];
            }
        }

        return $loginEmails;
    }

    private function deleteTheClientsAdded(): void
    {
        $clients = $this->cognito->listUserPoolClients(['UserPoolId' => $this->pool->id(), 'MaxResults' => self::LIST_SIZE])['UserPoolClients'] ?? [];

        foreach ($clients as $client) {
            if (!in_array($client['ClientName'], UserPool::CLIENT_NAMES_OF_THE_MCP_SERVER, true)) {
                $this->cognito->deleteUserPoolClient(['UserPoolId' => $this->pool->id(), 'ClientId' => $client['ClientId']]);
            }
        }
    }
}
