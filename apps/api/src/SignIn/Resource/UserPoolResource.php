<?php

namespace App\SignIn\Resource;

/**
 * Where the SPA signs its members in: the user pool and its client, public by nature, and the endpoint of
 * Cognito when it is not the one of AWS (MiniStack, through nginx locally).
 */
final readonly class UserPoolResource
{
    public function __construct(
        public string $region,
        public string $userPoolId,
        public string $clientId,
        public ?string $endpoint,
    ) {
    }
}
