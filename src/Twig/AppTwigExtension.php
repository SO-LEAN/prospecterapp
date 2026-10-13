<?php

namespace App\Twig;

use App\Service\ImageService;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppTwigExtension extends AbstractExtension
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('image_operation', $this->buildOperationUrl(...)),
        ];
    }

    public function buildOperationUrl(string $url, string $operation, mixed ...$args): string
    {
        try {
            return $this->imageService->buildOperationUrl($url, $operation, $args);
        } catch (Exception) {
            return '';
        }
    }
}
