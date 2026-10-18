<?php

namespace App\Doc;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The icon the browsers ask for the pages of the api (its doc): the one of the SPA, a copy in `public/favicon.svg`,
 * so that the api answers no 404 to every page view, its function serving no static file.
 */
final readonly class ShowFavicon
{
    private const int A_WEEK = 604800;

    public function __construct(#[Autowire('%kernel.project_dir%/public/favicon.svg')] private string $icon)
    {
    }

    #[Route('/favicon.ico', name: 'favicon', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response((string) file_get_contents($this->icon), headers: ['Content-Type' => 'image/svg+xml'])
            ->setCache(['public' => true, 'max_age' => self::A_WEEK]);
    }
}
