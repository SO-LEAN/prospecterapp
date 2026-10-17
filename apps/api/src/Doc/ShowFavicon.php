<?php

namespace App\Doc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The icon the browsers ask for the pages of the api (its doc): the one of the SPA, so that the api answers no 404 to
 * every page view, its function serving no static file.
 */
final readonly class ShowFavicon
{
    private const string ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><title>Prospecter</title><rect width="32" height="32" rx="8" fill="#3b5bdb"/><path d="M10 22V10h6.5a4 4 0 0 1 0 8H10" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    private const int A_WEEK = 604800;

    #[Route('/favicon.ico', name: 'favicon', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(self::ICON, headers: ['Content-Type' => 'image/svg+xml'])
            ->setCache(['public' => true, 'max_age' => self::A_WEEK]);
    }
}
