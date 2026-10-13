<?php

namespace App\Twig;

use App\Service\ImageService;
use Twig\Attribute\AsTwigFilter;

class AppTwigExtension
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    #[AsTwigFilter(name: 'image_operation')]
    public function buildOperationUrl(string $url, string $operation, mixed ...$args): string
    {
        try {
            return $this->imageService->buildOperationUrl($url, $operation, $args);
        } catch (\Exception) {
            return '';
        }
    }
}
