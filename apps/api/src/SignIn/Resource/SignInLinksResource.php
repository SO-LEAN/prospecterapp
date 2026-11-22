<?php

declare(strict_types=1);

namespace App\SignIn\Resource;

use App\Shared\Resource\LinkResource;

final readonly class SignInLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $changeMyLoginEmail,
        public LinkResource $changeMyPassword,
    ) {
    }
}
