<?php

declare(strict_types=1);

namespace App\Demo;

use App\Shared\Typed;
use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

/**
 * Gives a kept user pool the demonstration's members back (ADR 0052). It deletes the users and the clients added since,
 * and gives each demonstration member their login email and password back. A sign-out everywhere needs nothing:
 * MiniStack revokes only the tokens issued before its second.
 */
final readonly class DemoMembers
{
    private const int LIST_SIZE = 60;

    public function __construct(
        private UserPool $pool,
        private Credentials $credentials,
        private CognitoIdentityProviderClient $cognito,
    ) {
    }

    public function restore(): void
    {
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
            foreach (Typed::documents($page['Users'] ?? []) as $user) {
                $username = Typed::string($user['Username']);

                if (!array_key_exists($username, DemoCredentials::LOGIN_EMAILS)) {
                    $this->cognito->adminDeleteUser(['UserPoolId' => $this->pool->id(), 'Username' => $username]);

                    continue;
                }

                $loginEmails[$username] = Typed::string(array_column(Typed::documents($user['Attributes'] ?? []), 'Value', 'Name')['email']);
            }
        }

        return $loginEmails;
    }

    private function deleteTheClientsAdded(): void
    {
        $clients = $this->cognito->listUserPoolClients(['UserPoolId' => $this->pool->id(), 'MaxResults' => self::LIST_SIZE])['UserPoolClients'] ?? [];

        foreach (Typed::documents($clients) as $client) {
            if (!in_array($client['ClientName'], UserPool::CLIENT_NAMES_OF_THE_MCP_SERVER, true)) {
                $this->cognito->deleteUserPoolClient(['UserPoolId' => $this->pool->id(), 'ClientId' => Typed::string($client['ClientId'])]);
            }
        }
    }
}
