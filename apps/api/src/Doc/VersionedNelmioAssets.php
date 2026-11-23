<?php

declare(strict_types=1);

namespace App\Doc;

use Composer\InstalledVersions;
use Nelmio\ApiDocBundle\Render\Html\AssetsMode;
use Nelmio\ApiDocBundle\Render\Html\GetNelmioAsset;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The doc loads Swagger UI from the CDN (nelmio_api_doc's assets_mode). There, NelmioApiDocBundle names its files
 * without a version, which serves its repository's latest state. This class asks for them at the version installed
 * here.
 */
final class VersionedNelmioAssets extends AbstractExtension
{
    private const string BUNDLE = 'nelmio/api-doc-bundle';
    private const string CDN = 'https://cdn.jsdelivr.net/gh/nelmio/NelmioApiDocBundle@%s/public/%s';

    public function __construct(private readonly GetNelmioAsset $assets)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('nelmioAsset', $this, ['is_safe' => ['html']])];
    }

    public function __invoke(string $assetsMode, string $asset): string
    {
        return ($this->assets)($assetsMode, AssetsMode::CDN === $assetsMode ? $this->versioned($asset) : $asset);
    }

    private function versioned(string $asset): string
    {
        return sprintf(self::CDN, InstalledVersions::getPrettyVersion(self::BUNDLE), $asset);
    }
}
