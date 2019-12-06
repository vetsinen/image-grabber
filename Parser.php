<?php


namespace Serpstat;


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
    private object $deadLinks;

    function __construct(string $url)
    {
        if ((strpos($url, "https://") === false && strpos($url, "http://") === false)) {
            $url = "https://{$url}";
        }
        $this->startpage = $url;
        $this->domain = parse_url($url, PHP_URL_HOST);
        $this->protocol = parse_url($url, PHP_URL_SCHEME);

        $this->opts = stream_context_create([
            'https' => [
                'method' => "GET",
                'header' => implode('\r\n', ["Accept-language: en", "Cookie: foo=bar"])
            ]
        ]);

        $this->imageLinksBasket = [];
        $this->deadLinks = new Set();
    }

    public function getAbsoluteLinkFromPath(string $path)
    {
        return "{$this->protocol}://{$this->domain}{$path}";
    }

    public function getContentFromPath(string $path)
    {
        return file_get_contents("{$this->protocol}://{$this->domain}{$path}");
    }


    public function execute(): string
    {
        if ($content = file_get_contents($this->startpage)) {
            $currentPath = parse_url($this->startpage, PHP_URL_PATH);
            $ptp = [$currentPath];
            do {
                $flag = false;
                $newptp = [];
                var_dump($ptp);
                foreach ($ptp as $path) {
                    if (!key_exists($path, array_keys($this->imageLinksBasket)))
                    //we have not processed page
                    {
                        $flag = true;
                        $absoluteUrl = $this->getAbsoluteLinkFromPath($path);
                        $content = $this->getContentFromPath($path);
                        $srcs = $this->getImageLinksFromPage($content);
                        if (count($srcs)>0)
                        {
                            $this->imageLinksBasket[]=[$path=>$srcs];
                        }
                        $pathes = $this->getPageLinksFromPage($content);
                        $newptp = array_merge($newptp, $pathes);
                    }
                }
                $ptp = $newptp;
            } while ($flag);
            var_dump($this->imageLinksBasket);
            return '';
            $srcs = $this->getImageLinksFromPage($content);
            $this->pageImages = [$currentPath => $srcs];
            $rez = $this->getPageLinksFromPage($content);
            var_dump($rez);
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
        if (strpos($url, "https://") === false && strpos($url, "http://") === false) {
            $url = "{$this->protocol}://{$this->domain}/{$url}";
        }
        if (!parse_url($url, PHP_URL_PATH)) $url = $url . '/';
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
//                echo "{$link[2]} - {$this->urlNormalize($link[2])} \n";
                $fulllink = $this->urlNormalize($link[2]);
                if (file_get_contents($fulllink) &&
                    !in_array(parse_url($fulllink, PHP_URL_PATH), $rez))
                {
                    $rez[] = parse_url($fulllink, PHP_URL_PATH);
                }
            }
        }
        return $rez;
    }

    private function parseImages(string $content, string $domain)
    {
        preg_match_all('/<img[^>]+>/i', $content, $result);

        foreach ($result[0] as $img_tag) {
            preg_match_all('/(src)=("[^"]*")/i', $img_tag, $img, PREG_SET_ORDER);
            $this->saveImageUrl($domain, $img);
        }
    }

    private function parseSubUrls(string $content)
    {
        preg_match_all('/(href)=("\/[a-zA-Z^"?]*")/i', $content, $links, PREG_SET_ORDER);

        if (!empty($links)) {
            foreach ($links as $link) {
                $subPage = trim($link[2], '"');
                if (!in_array($subPage, $this->links)) {
                    $this->links[] = $subPage;
                    $subContent = $this->getContent("{$this->protocol}://{$this->domain}{$subPage}");
                    $this->parseImages($subContent, $this->domain . $subPage);
                    $this->parseSubUrls($subContent);
                }
            }
        }
    }

    private function saveImageUrl(string $domain, array $images)
    {
        foreach ($images as $image) {
            if (!empty($image)) {
                fputcsv($this->file, [$domain, $this->checkImageUrl($image[2], $domain)], ";");
            }
        }
    }

    /**
     * @param string $url
     * @param string $domain
     * @return string
     */
    private function checkImageUrl(string $url, string $domain): string
    {
        $url = trim($url, '""');
        switch ($url[0]) {
            case "/":
                return trim($domain, "/") . $url;
            case "h":
                return $url;
            default:
                return "{$domain}/{$url}";
        }
    }

    /**
     * @param string $url
     * @return string
     */
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
        return file_get_contents($url,false,$opts)==="";
    }
}

class Set
{
    public $set=[];
    public function add($el)
    {
        if (!in_array($el,$this->set)){$this->set[]=$el;}
    }
    public function exists($el)
    {
        return in_array($el,$this->set);
    }
    public function clear(){$this->set=[];}
}