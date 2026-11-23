<?php

declare(strict_types=1);

namespace App\SignIn\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The facade's metadata as an authorization server (RFC 8414), with the RFC's field names. The facade offers the
 * authorization code with PKCE S256. It knows the AIs by their Client ID Metadata Document, without any secret of
 * theirs and without registration (ADR 0157).
 */
final readonly class AuthorizationServerMetadataResource
{
    /** @var list<string> */
    #[SerializedName('response_types_supported')]
    public array $responseTypesSupported;

    /** @var list<string> */
    #[SerializedName('grant_types_supported')]
    public array $grantTypesSupported;

    /** @var list<string> */
    #[SerializedName('code_challenge_methods_supported')]
    public array $codeChallengeMethodsSupported;

    /** @var list<string> */
    #[SerializedName('token_endpoint_auth_methods_supported')]
    public array $tokenEndpointAuthMethodsSupported;

    #[SerializedName('client_id_metadata_document_supported')]
    public bool $clientIdMetadataDocumentSupported;

    public function __construct(
        public string $issuer,
        #[SerializedName('authorization_endpoint')]
        public string $authorizationEndpoint,
        #[SerializedName('token_endpoint')]
        public string $tokenEndpoint,
    ) {
        $this->responseTypesSupported = ['code'];
        $this->grantTypesSupported = ['authorization_code', 'refresh_token'];
        $this->codeChallengeMethodsSupported = ['S256'];
        $this->tokenEndpointAuthMethodsSupported = ['none'];
        $this->clientIdMetadataDocumentSupported = true;
    }
}
