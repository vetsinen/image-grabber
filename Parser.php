<?php

namespace Serpstat;


class Parser
{
    private string $startpage;
    private string $domain;
    private string $protocol;
    private $file;
    private array $imageLinksBasket;
    private array $deadLinks;

    function __construct(string $url)
    {
        $url = trim($url);
        if ((strpos($url, "https://") !== 0 && strpos($url, "http://") !== 0)) {
            $url = "https://{$url}";
        }
        $this->domain = parse_url($url, PHP_URL_HOST);
        echo $this->domain;
        $this->protocol = parse_url($url, PHP_URL_SCHEME);
        $this->startpage = $this->urlNormalize($url);
        $this->imageLinksBasket = [];
        $this->deadLinks = [];
    }

    public function execute(): string
    {
        if ($content = file_get_contents($this->startpage) &&
            $this->file = fopen("reports/{$this->domain}.csv", "w")) {
            $currentPath = parse_url($this->startpage, PHP_URL_PATH);
            $ptp = [$currentPath];
            while ($ptp) {
                $newptp = [];
                foreach ($ptp as $path) {
                    if ($this->isAlive($path)) //we have not processed page
                    {
                        $content = $this->getContentFromPath($path);
                        $srcs = $this->getImageLinksFromPage($content);
                        if (count($srcs) > 0) {
                            $this->imageLinksBasket[$path] = $srcs;
                        }
                        $pathes = $this->getPageLinksFromPage($content);
                        $this->markAsDead($path);
                        $newptp = array_merge($newptp, $pathes);
                    }
                    $this->markAsDead($path);
                }
                $ptp = $newptp;
            }
            $this->saveImageLinks();
            fclose($this->file);
            return "reports/{$this->domain}.csv";
        } else {
            return "Failed to parse url : {$this->startpage} \n";
        }
    }

    function urlNormalize(string $url): string
    {
        $url = str_replace('"', '', $url);
        $url = trim($url);
        if (strlen($url) < 1) {
            $url = '/';
        }

        if (!$this->isAbsoluteHttpOrHttpsLink($url)) {
            if ($url[0] !== '/') {
                $url = '/' . $url;
            }
            $url = "{$this->protocol}://{$this->domain}{$url}";
        }

        if (is_null(parse_url($url, PHP_URL_PATH))) $url = $url . '/';
        return $url;
    }

    private function getImageLinksFromPage(string $content): array
    {
        $rez = [];
        preg_match_all('/<img[^>]+>/i', $content, $result);
        foreach ($result[0] as $img_tag) {
            preg_match_all('/(src)=("[^"]*")/i', $img_tag, $img, PREG_SET_ORDER);
            if ($img) {
                $rez[] = $this->urlNormalize($img[0][2]);
            }
        }
        return $rez;
    }

    private function getPageLinksFromPage(string $content): array
    {
        $rez = [];
        $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
        preg_match_all("/$regexp/siU", $content, $links, PREG_SET_ORDER);
        if (!empty($links)) {
            foreach ($links as $link) {
                {
                    $fulllink = $this->urlNormalize($link[2]);
                    if ($this->isAlienDomainLink($fulllink) || !$this->isAlive($fulllink)) {
                        $this->markAsDead($fulllink);
                    }
                    elseif (
                        $this->isAlive($fulllink) &&
                        !in_array(parse_url($fulllink, PHP_URL_PATH), $rez))
                    {
                        $rez[] = parse_url($fulllink, PHP_URL_PATH);
                    }
                }
            }
        }
        return $rez;
    }

    private function saveImageLinks():void
    {
        foreach ($this->imageLinksBasket as $key => $images) {
            foreach ($images as $imageLink) {
                $fulllink = $this->urlNormalize($key);
                fputcsv($this->file, [$fulllink, $imageLink], ";");
            }
        }
    }

    public function markAsDead($el):void
    {
        if (!in_array($el, $this->deadLinks)) {
            $this->deadLinks[] = $el;
        }
    }

    public function isAlive(string $el):bool
    {
        return !in_array($el, $this->deadLinks) && $this->checkUrl($this->urlNormalize($el));
    }

    private function checkUrl(string $url):bool
    {
        $opts = stream_context_create([
            'http' => [
                'method' => "HEAD",
                'header' => implode('\r\n', ["Accept-language: en", "Cookie: foo=bar"])
            ]
        ]);
        return file_get_contents($url, false, $opts) === "";
    }

    private function isAbsoluteHttpOrHttpsLink($link):bool
    {
        return strpos($link, "https://") === 0 || strpos($link, "http://") === 0;
    }

    private function isAlienDomainLink($link):bool
    {
        return $this->isAbsoluteHttpOrHttpsLink($link)
            && parse_url($link, PHP_URL_HOST) !== $this->domain;
    }

    public function getContentFromPath(string $path):string
    {
        return file_get_contents("{$this->protocol}://{$this->domain}{$path}");
    }
}