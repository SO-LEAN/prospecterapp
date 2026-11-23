<?php

declare(strict_types=1);

namespace App\SignIn;

use App\Shared\Links;
use App\SignIn\Resource\SignInLinksResource;
use App\SignIn\Resource\SignInResource;

/**
 * Reads what a member signs in with from the user pool, with the changes the member may make to it.
 */
final readonly class SignInReader
{
    public function __construct(private Credentials $credentials, private Links $links)
    {
    }

    public function read(string $memberId): SignInResource
    {
        return new SignInResource($this->credentials->loginEmailOf($memberId), new SignInLinksResource(
            $this->links->to('api_me_sign_in'),
            $this->links->to('api_me_change_login_email'),
            $this->links->to('api_me_change_password'),
        ));
    }
}
