<?php

namespace App\Traits;

use Exception;

trait ResponseParsing
{
    /**
     * Get file name from header
     * @source: https://gist.github.com/kitsaels/eefe676414b8ca225f82d04f942045c8
     * @param string $header - value of the header
     * @return string
     */
    public function getFilenameFromHeader(string $header)
    {
        if (preg_match('/filename="(.+?)"/', $header, $matches)) {
            return $matches[1];
        }
        if (preg_match('/filename=([^; ]+)/', $header, $matches)) {
            return rawurldecode($matches[1]);
        }
        throw new Exception(__FUNCTION__ . ": Filename not found", 1001);
    }
}
