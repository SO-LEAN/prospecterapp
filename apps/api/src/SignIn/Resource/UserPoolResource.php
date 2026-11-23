<?php

declare(strict_types=1);

namespace App\SignIn\Resource;

/**
 * Where the SPA signs its members in: the user pool and its client, which are public by nature. It also gives the
 * Cognito endpoint when it is not the AWS one (MiniStack through nginx, locally).
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
