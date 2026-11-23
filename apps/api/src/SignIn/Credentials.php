<?php

declare(strict_types=1);

namespace App\SignIn;

use App\Shared\Typed;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * What a member signs in with, which Cognito keeps (ADR 0032, 0052): a login email and a password. The login email is
 * unique on the platform, and distinct from the contact email in the member's profile. The Cognito user's name is the
 * member id.
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

        return Typed::string(array_column(Typed::array($attributes), 'Value', 'Name')['email']);
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
     * Checks the email before writing. As a safety net, Cognito also refuses a second holder of a verified alias.
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

        return Typed::strings(array_column(Typed::array($users), 'Username'));
    }

    /**
     * @return list<array{Name: string, Value: string}>
     */
    private static function verified(string $loginEmail): array
    {
        return [['Name' => 'email', 'Value' => $loginEmail], ['Name' => 'email_verified', 'Value' => 'true']];
    }
}
