<?php

namespace App\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * What I sign in with, and how I change it.
 */
final readonly class MeResource
{
    public function __construct(
        public string $loginEmail,
        #[SerializedName('_links')]
        public MeLinksResource $links,
    ) {
    }
}
