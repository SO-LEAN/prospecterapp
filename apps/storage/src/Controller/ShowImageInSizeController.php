<?php

namespace App\Controller;

use App\Bucket;
use App\ImageSize;
use App\Resizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Reached only when a size of an image is not in the bucket yet: once made, the bucket serves it.
 */
final readonly class ShowImageInSizeController
{
    public function __construct(private Resizer $resizer)
    {
    }

    #[Route('/storage/{size}/{path}', name: 'image_in_size', requirements: ['size' => '[a-z]+', 'path' => Requirement::CATCH_ALL], methods: ['GET'])]
    public function __invoke(string $size, string $path): Response
    {
        $imageSize = ImageSize::named($size) ?? throw new NotFoundHttpException(sprintf('No size %s', $size));
        $copy = $this->resizer->copy($path, $imageSize);

        return new Response($copy->contents, headers: ['Content-Type' => $copy->contentType])
            ->setCache(['public' => true, 'max_age' => Bucket::A_YEAR, 'immutable' => true]);
    }
}
