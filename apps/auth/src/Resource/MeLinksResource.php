<?php

namespace App\Resource;

final readonly class MeLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $changeMyLoginEmail,
        public LinkResource $changeMyPassword,
    ) {
    }
}
