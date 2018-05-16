<?php

namespace App\Service\Image;

interface Decoder
{
    /**
     * @param $input
     *
     * @return mixed
     */
    public function decode($input);
}
