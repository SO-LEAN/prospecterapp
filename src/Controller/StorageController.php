<?php

namespace App\Controller;

use App\Service\LocalStorageService;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use  Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class StorageController extends Controller
{
    /**
     * @param string $filePath
     *
     * @return BinaryFileResponse
     *
     * @Route("/{filePath}", name="display")
     * @Cache(expires="+1 year", public=true, maxage="31536000", smaxage="31536000")
     */
    public function display($filePath)
    {
        /**
         * @var File
         */
        $file = $this->get(LocalStorageService::class)->get($filePath);

        return new BinaryFileResponse($file);
    }
}
