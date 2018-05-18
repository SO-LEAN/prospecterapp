<?php

namespace App\Twig;

use \Exception;
use Twig_Extension;
use Twig_Environment;
use Twig_SimpleFilter;
use App\Service\ImageService;
use Twig_Extension_InitRuntimeInterface;

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

    /**
     * @param ImageService $imageService
     */
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

    /**
     * @param Twig_Environment $environment
     */
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
     * @param string $url
     * @param string $operation
     * @param mixed  ...$args
     *
     * @return string
     */
    public function buildOperationUrl(string $url, string $operation = 'rsz', ...$args)
    {
        try {
            $test = $this->imageService->buildOperationUrl($url, $operation, $args);
        } catch(Exception $e) {
            return '';
        }

        return $test;
    }
}
