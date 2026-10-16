<?php

namespace App\SignIn;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * What a member signs in with, kept by Cognito (ADR 0032, 0052): a login email, unique on the platform and
 * distinct from the contact email of its profile, and a password. The user is named by the member id.
 */
final readonly class Credentials
{
    public function __construct(private CognitoIdentityProviderClient $cognito, private UserPool $pool)
    {
    }

    public function register(string $memberId, string $loginEmail, string $password): void
    {
        $this->ensureFree($loginEmail, $memberId);
        $this->cognito->adminCreateUser([
            'UserPoolId' => $this->pool->id(),
            'Username' => $memberId,
            'UserAttributes' => self::verified($loginEmail),
            'MessageAction' => 'SUPPRESS',
        ]);
        $this->changePassword($memberId, $password);
    }

    public function loginEmailOf(string $memberId): string
    {
        $attributes = $this->cognito->adminGetUser(['UserPoolId' => $this->pool->id(), 'Username' => $memberId])['UserAttributes'] ?? [];

        return (string) array_column($attributes, 'Value', 'Name')['email'];
    }

    /**
     * @throws LoginEmailTaken
     */
    public function changeLoginEmail(string $memberId, string $loginEmail): void
    {
        $this->ensureFree($loginEmail, $memberId);

        try {
            $this->cognito->adminUpdateUserAttributes(['UserPoolId' => $this->pool->id(), 'Username' => $memberId, 'UserAttributes' => self::verified($loginEmail)]);
        } catch (CognitoIdentityProviderException $failure) {
            throw 'AliasExistsException' === $failure->getAwsErrorCode() ? new LoginEmailTaken() : $failure;
        }
    }

    public function changePassword(string $memberId, string $password): void
    {
        $this->cognito->adminSetUserPassword(['UserPoolId' => $this->pool->id(), 'Username' => $memberId, 'Password' => $password, 'Permanent' => true]);
    }

    public function memberSigningInWith(string $loginEmail): ?string
    {
        return $this->usersSigningInWith($loginEmail)[0] ?? null;
    }

    /**
     * Checked before writing, Cognito refusing a second holder of a verified alias as a safety net.
     *
     * @throws LoginEmailTaken
     */
    private function ensureFree(string $loginEmail, string $memberId): void
    {
        if ([] !== array_diff($this->usersSigningInWith($loginEmail), [$memberId])) {
            throw new LoginEmailTaken();
        }
    }

    /**
     * @return list<string> the member ids
     */
    private function usersSigningInWith(string $loginEmail): array
    {
        $users = $this->cognito->listUsers(['UserPoolId' => $this->pool->id(), 'Filter' => sprintf('email = "%s"', addcslashes($loginEmail, '"\\'))])['Users'] ?? [];

        return array_column($users, 'Username');
    }

    /**
     * @return list<array{Name: string, Value: string}>
     */
    private static function verified(string $loginEmail): array
    {
        return [['Name' => 'email', 'Value' => $loginEmail], ['Name' => 'email_verified', 'Value' => 'true']];
    }
}
