<?php

namespace App\Service;

class UtilsService
{
    /**
     * @param string $string
     * @return string
     */
    public static function cleanInput(string $string)
    {
        return htmlspecialchars(strip_tags(trim($string)), ENT_NOQUOTES);
    }
}
