<?php


namespace Serpstat;


class Url
{
    public function normalize(string $url):string
    {
        return strpos($url, "https") === false ? "https://{$url}" : $url;
    }

}