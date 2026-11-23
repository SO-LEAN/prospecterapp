<?php

declare(strict_types=1);

namespace App\Doc;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The icon that browsers ask for on the API's pages (its doc). It is the SPA's icon, copied in `public/favicon.svg`. So
 * the API answers no 404 on each page view, since its function serves no static file.
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
