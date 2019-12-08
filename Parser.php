<?php


namespace Serpstat;


use http\Url;

class Parser
{
    private string $url;
    private string $startpage;
    private $domain;
    private $protocol;

    /**
     * @var resource
     */
    private $file;

    private $opts;
    private array $pageImages;
    public array $links;
    private array $deadLinks;

    function __construct(string $url)
    {
        if ((strpos($url, "https://") === false && strpos($url, "http://") === false)) {
            $url = "https://{$url}";
        }
        $this->startpage = $url;
        $this->domain = parse_url($url, PHP_URL_HOST);
        echo $this->domain;
        $this->protocol = parse_url($url, PHP_URL_SCHEME);
        $this->opts = stream_context_create([
            'https' => [
                'method' => "GET",
                'header' => implode('\r\n', ["Accept-language: en", "Cookie: foo=bar"])
            ]
        ]);

        $this->imageLinksBasket = [];
        $this->deadLinks = [];
    }

    public function getAbsoluteLinkFromPath(string $path)
    {
        return "{$this->protocol}://{$this->domain}{$path}";
    }

    public function getContentFromPath(string $path)
    {
        return file_get_contents("{$this->protocol}://{$this->domain}{$path}");
    }

    public function markAsDead($el)
    {
        if (!in_array($el, $this->deadLinks)) {
            $this->deadLinks[] = $el;
        }
    }

    public function isAlive(string $el)
    {
        return !in_array($el, $this->deadLinks) && $this->checkUrl($this->urlNormalize($el));
    }

    public function execute(): string
    {
        if ($content = file_get_contents($this->startpage)) {
            $currentPath = parse_url($this->startpage, PHP_URL_PATH);
            $ptp = [$currentPath];
            do {
                $flag = false;
                $newptp = [];
                if (!$ptp) {continue;}
                foreach ($ptp as $path) {
                    if ($this->isAlive($path)) //we have not processed page
                    {
                        echo "investigating $path...\n";
                        $flag = true;
                        $absoluteUrl = $this->getAbsoluteLinkFromPath($path);
                        $content = $this->getContentFromPath($path);
                        $srcs = $this->getImageLinksFromPage($content);
                        if (count($srcs) > 0) {
                            $this->imageLinksBasket[] = [$path => $srcs];
                        }
                        $pathes = $this->getPageLinksFromPage($content);
                        $this->markAsDead($path);
                        $newptp = array_merge($newptp, $pathes);
                    } else {
                        $this->markAsDead($path);
                    }
                }
                $ptp = $newptp;
            } while ($flag);
            var_dump($this->imageLinksBasket);
            $this->saveImageLinks();
            return '';
            $this->file = fopen("reports/{$this->domain}.csv", "w");

            fclose($this->file);
            return "reports/{$this->domain}.csv";
        } else {
            return "Failed to parse url : {$this->url} " . PHP_EOL;
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

    private function getPageLinksFromPage(string $content)
    {
        $rez = [];
        $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
        preg_match_all("/$regexp/siU", $content, $links, PREG_SET_ORDER);
        if (!empty($links)) {
            foreach ($links as $link) {
//                echo "{$link[2]} - {$this->urlNormalize($link[2])} \n";
                {
                    $fulllink = $this->urlNormalize($link[2]);
                    $domain = parse_url($fulllink, PHP_URL_HOST);
//                    echo "before norm: " . $link[2] . "=>";
//                    echo $fulllink;
//                    echo $domain === $this->domain ? "\n" : " -external link\n";
//                    echo "fulllink: $fulllink ";
//                    echo !$this->isAlienDomainLink($fulllink) &&
//                        $this->checkUrl($fulllink) &&
//                        !in_array(parse_url($fulllink, PHP_URL_PATH), $rez)?"added\n":"wrong\n";

                    if (!$this->isAlive($fulllink)) {
                        $this->markAsDead($fulllink);
                    }

                    if ($this->isAlive($fulllink) &&
                        !$this->isAlienDomainLink($fulllink) &&
                        !in_array(parse_url($fulllink, PHP_URL_PATH), $rez)) {
                        $rez[] = parse_url($fulllink, PHP_URL_PATH);
                    }

                }
            }
            return $rez;
        }
    }

    private function saveImageLinks()
    {
        foreach ($this->imageLinksBasket as $key => $images) {
            foreach ($images as $image) {
                echo "$key - $image";
            }
        }
        //fputcsv($this->file, [$domain, $this->checkImageUrl($image[2], $domain)], ";");
    }

    private function getContent(string $url): string
    {
        return file_get_contents($url, false, $this->opts);
    }

    private function checkUrl(string $url)
    {
        $opts = stream_context_create([
            'http' => [
                'method' => "HEAD",
                'header' => implode('\r\n', ["Accept-language: en", "Cookie: foo=bar"])
            ]
        ]);
        return file_get_contents($url, false, $opts) === "";
    }

    private function isAbsoluteHttpOrHttpsLink($link)
    {
        return strpos($link, "https://") > -1 || strpos($link, "http://") > -1;
    }

    private function isAlienDomainLink($link)
    {
        return $this->isAbsoluteHttpOrHttpsLink($link)
            && parse_url($link , PHP_URL_HOST)!== $this->domain;
    }
}