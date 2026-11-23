<?php

declare(strict_types=1);

namespace App\Shared\Mcp;

use App\Shared\Images;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The images an AI gives by their web address. They are downloaded as the screen sends them (ADR 0168): 2 MB at most,
 * in JPEG, PNG or WebP. Any other image is refused, as the screen's image would be.
 */
final readonly class ImagesOnTheWeb
{
    public function __construct(private FilesOnTheWeb $files, private ValidatorInterface $validator)
    {
    }

    /**
     * @template TResult
     *
     * @param \Closure(File): TResult $use what to do with the image, which is kept until then (the commands run within the request)
     *
     * @return TResult
     *
     * @throws ValidationFailedException when the image is unreachable, or when Prospecter does not accept it
     */
    public function whileDownloaded(WebAddressInput $address, \Closure $use): mixed
    {
        return $this->files->whileDownloaded($address, Images::LARGEST_BYTES, fn (File $image): mixed => $use($this->valid($image)));
    }

    private function valid(File $image): File
    {
        $violations = $this->validator->validate($image, Images::constraint());

        if (0 < $violations->count()) {
            throw new ValidationFailedException($image, $violations);
        }

        return $image;
    }
}
