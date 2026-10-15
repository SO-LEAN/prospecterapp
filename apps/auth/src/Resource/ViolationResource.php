<?php

namespace App\Resource;

final readonly class ViolationResource
{
    /**
     * @param string $propertyPath input field in error, empty for the whole payload
     */
    public function __construct(
        public string $propertyPath,
        public string $title,
    ) {
    }
}
