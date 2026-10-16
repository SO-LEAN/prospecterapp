<?php

namespace App\SignIn\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * What I sign in with, and how I change it.
 */
final readonly class SignInResource
{
    public function __construct(
        public string $loginEmail,
        #[SerializedName('_links')]
        public SignInLinksResource $links,
    ) {
    }
}
