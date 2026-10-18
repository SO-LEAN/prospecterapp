<?php

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;

final readonly class ViolationResource
{
    /**
     * @param string                $propertyPath input field in error, empty for the whole payload
     * @param array<string, string> $context      what the refusal is about, by name
     */
    public function __construct(
        public string $propertyPath,
        public string $title,
        #[OA\Property(description: 'The refusal of the business named, for a client to word it in its language (ADR 0073); absent for the violations of the input shape', example: 'invalidPhoneNumber')]
        public ?string $reason = null,
        #[OA\Property(description: 'What the refusal is about, by name', type: 'object', example: ['phoneNumber' => '03554411'], additionalProperties: new OA\AdditionalProperties(type: 'string'))]
        public array $context = [],
    ) {
    }
}
