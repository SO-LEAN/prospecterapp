<?php

namespace App\Traits;


trait Base64Urlize
{
    /**
     * @param $data
     *
     * @return string
     */
    protected function urlize($data)
    {
        return rtrim(strtr($data, '+/', '-_'), '=');
    }

    /**
     * @param $data
     *
     * @return bool|string
     */
    protected function unurlize($data)
    {
        return  strtr($data, '-_', '+/').str_repeat('=', 3 - (3 + strlen($data)) % 4);
    }
}
