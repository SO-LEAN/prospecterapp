<?php

namespace App;

use Aws\CloudFront\CloudFrontClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * On AWS, CloudFront keeps the files for a year (ADR 0070): the paths of the removed ones are invalidated.
 */
final readonly class CloudFrontPublicCache implements PublicCache
{
    public function __construct(
        private CloudFrontClient $cloudFront,
        #[Autowire(env: 'CLOUDFRONT_DISTRIBUTION_ID')]
        private string $distributionId,
        private string $publicUrl,
    ) {
    }

    public function forget(array $keys): void
    {
        $paths = array_map($this->pathOf(...), $keys);

        $this->cloudFront->createInvalidation([
            'DistributionId' => $this->distributionId,
            'InvalidationBatch' => [
                'CallerReference' => bin2hex(random_bytes(16)),
                'Paths' => ['Quantity' => count($paths), 'Items' => $paths],
            ],
        ]);
    }

    private function pathOf(string $key): string
    {
        return sprintf('%s/%s', rtrim((string) parse_url($this->publicUrl, PHP_URL_PATH), '/'), $key);
    }
}
