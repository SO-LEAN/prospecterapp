<?php

namespace App\Controller;

use App\ImageSize;
use App\Resizer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Reached only the first time a size of an image is asked: nginx serves the copy once it exists.
 */
final readonly class ShowImageInSize
{
    public function __construct(private Resizer $resizer)
    {
    }

    #[Route('/storage/{size}/{path}', name: 'image_in_size', requirements: ['size' => '[a-z]+', 'path' => Requirement::CATCH_ALL], methods: ['GET'])]
    #[Cache(maxage: 31536000, smaxage: 31536000, public: true)]
    public function __invoke(string $size, string $path): BinaryFileResponse
    {
        $imageSize = ImageSize::named($size) ?? throw new NotFoundHttpException(sprintf('No size %s', $size));

        return new BinaryFileResponse($this->resizer->copy($path, $imageSize));
    }
}
