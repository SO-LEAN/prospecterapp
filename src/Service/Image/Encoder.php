<?php

namespace App\Service\Image;

interface Encoder
{
    /**
     * @param $input
     *
     * @return mixed
     */
    public function encode($input);
}
