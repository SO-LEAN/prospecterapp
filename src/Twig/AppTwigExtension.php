<?php

namespace App\Twig;

use App\Service\ImageService;
use Exception;
use Twig_Environment;
use Twig_Extension;
use Twig_Extension_InitRuntimeInterface;
use Twig_SimpleFilter;

class AppTwigExtension extends Twig_Extension implements Twig_Extension_InitRuntimeInterface
{
    /**
     * @var Twig_Environment
     */
    private $environment;
    /**
     * @var ImageService
     */
    private $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'app_extension';
    }

    public function initRuntime(Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('image_operation', [$this, 'buildOperationUrl']),
        ];
    }

    /**
     * @param mixed ...$args
     *
     * @return string
     */
    public function buildOperationUrl(string $url, string $operation, ...$args)
    {
        try {
            $test = $this->imageService->buildOperationUrl($url, $operation, $args);
        } catch (Exception $e) {
            return '';
        }

        return $test;
    }
}
